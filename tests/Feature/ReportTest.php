<?php

namespace Tests\Feature;

use App\Exports\IncidentsSheet;
use App\Exports\StatisticsSheet;
use App\Exports\TicketsExport;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $techA;

    private User $techB;

    private Category $catX;

    private Category $catY;

    /** @var array<string, Ticket> */
    private array $t = [];

    /**
     * 4 tickets en septembre 2026 :
     *  t1 nouveau/normale/X, t2 en cours/critique/X (en retard, tech A, 90 min),
     *  t3 résolu/haute/Y (tech A, 6 h, 60 min), t4 fermé/faible/Y (tech B, 2 h, 30 min).
     */
    private function seedData(): void
    {
        $this->techA = User::factory()->technician()->create(['name' => 'Alpha', 'first_name' => 'Tech']);
        $this->techB = User::factory()->technician()->create(['name' => 'Bravo', 'first_name' => 'Tech']);
        $this->catX = Category::factory()->create(['name' => 'Climatisation']);
        $this->catY = Category::factory()->create(['name' => 'Réseau']);

        $base = ['deadline' => null, 'resolved_at' => null, 'assigned_to' => null];

        $this->t['t1'] = Ticket::factory()->create($base + [
            'status' => 'new', 'priority' => 'normal', 'category_id' => $this->catX->id,
            'created_at' => '2026-09-05 08:00:00',
        ]);
        $this->t['t2'] = Ticket::factory()->create([
            'status' => 'in_progress', 'priority' => 'critical', 'category_id' => $this->catX->id,
            'assigned_to' => $this->techA->id, 'deadline' => '2026-09-10 00:00:00', 'resolved_at' => null,
            'created_at' => '2026-09-06 08:00:00',
        ]);
        $this->t['t3'] = Ticket::factory()->create([
            'status' => 'resolved', 'priority' => 'high', 'category_id' => $this->catY->id,
            'assigned_to' => $this->techA->id, 'deadline' => null,
            'created_at' => '2026-09-01 08:00:00', 'resolved_at' => '2026-09-01 14:00:00',
        ]);
        $this->t['t4'] = Ticket::factory()->create([
            'status' => 'closed', 'priority' => 'low', 'category_id' => $this->catY->id,
            'assigned_to' => $this->techB->id, 'deadline' => null,
            'created_at' => '2026-09-02 08:00:00', 'resolved_at' => '2026-09-02 10:00:00',
        ]);

        TimeEntry::factory()->create(['ticket_id' => $this->t['t2']->id, 'user_id' => $this->techA->id, 'minutes' => 90]);
        TimeEntry::factory()->create(['ticket_id' => $this->t['t3']->id, 'user_id' => $this->techA->id, 'minutes' => 60]);
        TimeEntry::factory()->create(['ticket_id' => $this->t['t4']->id, 'user_id' => $this->techB->id, 'minutes' => 30]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ---------- Statistiques ----------

    public function test_statistics_are_computed_on_the_given_tickets(): void
    {
        $this->seedData();

        $stats = app(ReportService::class)->statistics(Ticket::query());

        $this->assertSame(4, $stats['total']);
        $this->assertSame(2, $stats['open']);
        $this->assertSame(2, $stats['resolved']);
        $this->assertSame(1, $stats['critical']);
        $this->assertSame(1, $stats['overdue']);
        $this->assertSame('4 h', $stats['avg_resolution_label']);
        $this->assertSame(180, $stats['time_spent_minutes']);
        $this->assertSame('3 h', $stats['time_spent_label']);
        $this->assertEquals(['new' => 1, 'assigned' => 0, 'in_progress' => 1, 'resolved' => 1, 'closed' => 1], $stats['by_status']);
        $this->assertEquals(['low' => 1, 'normal' => 1, 'high' => 1, 'critical' => 1], $stats['by_priority']);
        $this->assertEquals(['Climatisation' => 2, 'Réseau' => 2], $stats['by_category']);

        $technicians = collect($stats['by_technician'])->keyBy('name');
        $this->assertSame(['name' => 'Tech Alpha', 'assigned' => 2, 'done' => 1], $technicians['Tech Alpha']);
        $this->assertSame(['name' => 'Tech Bravo', 'assigned' => 1, 'done' => 1], $technicians['Tech Bravo']);
    }

    public function test_statistics_follow_the_filters(): void
    {
        $this->seedData();

        $stats = app(ReportService::class)->statistics(
            Ticket::query()->filter(['category_id' => $this->catY->id])
        );

        $this->assertSame(2, $stats['total']);
        $this->assertSame(0, $stats['open']);
        $this->assertSame(0, $stats['overdue']);
        $this->assertSame(90, $stats['time_spent_minutes']);
        $this->assertEquals(['Réseau' => 2], $stats['by_category']);
    }

    public function test_statistics_of_an_empty_set_do_not_fail(): void
    {
        $stats = app(ReportService::class)->statistics(Ticket::query());

        $this->assertSame(0, $stats['total']);
        $this->assertNull($stats['avg_resolution_hours']);
        $this->assertSame('N/A', $stats['avg_resolution_label']);
        $this->assertSame([], $stats['by_category']);
        $this->assertSame([], $stats['by_technician']);
    }

    // ---------- Rapport mensuel ----------

    public function test_monthly_report_covers_only_the_requested_month(): void
    {
        // Créé en août, résolu en septembre (120 h) : compte dans "résolus du mois", pas dans "créés".
        Ticket::factory()->create([
            'status' => 'resolved', 'created_at' => '2026-08-28 09:00:00', 'resolved_at' => '2026-09-02 09:00:00',
        ]);
        $sept1 = Ticket::factory()->create(['status' => 'new', 'created_at' => '2026-09-10 10:00:00', 'resolved_at' => null]);
        $sept2 = Ticket::factory()->create([ // 24 h
            'status' => 'closed', 'created_at' => '2026-09-15 10:00:00', 'resolved_at' => '2026-09-16 10:00:00',
        ]);
        Ticket::factory()->create(['status' => 'new', 'created_at' => '2026-10-02 10:00:00', 'resolved_at' => null]);

        $report = app(ReportService::class)->monthly(Carbon::create(2026, 9, 1));

        $this->assertSame('Septembre 2026', $report['label']);
        $this->assertSame([$sept1->id, $sept2->id], $report['tickets']->pluck('id')->all());
        $this->assertSame(2, $report['stats']['total']);
        $this->assertSame(2, $report['resolved_in_period']);
        $this->assertSame('3 j', $report['avg_resolution_period_label']); // moyenne de 120 h et 24 h = 72 h
    }

    public function test_monthly_pdf_view_renders_the_figures_and_tickets(): void
    {
        $this->seedData();
        $report = app(ReportService::class)->monthly(Carbon::create(2026, 9, 1));

        $html = view('reports.pdf', $report)->render();

        $this->assertStringContainsString('Septembre 2026', $html);
        $this->assertStringContainsString($this->t['t2']->title, $html);
        $this->assertStringContainsString('Tech Alpha', $html);
        $this->assertStringContainsString('Climatisation', $html);
        $this->assertStringContainsString('En cours', $html);
    }

    public function test_monthly_pdf_view_handles_a_month_without_tickets(): void
    {
        $report = app(ReportService::class)->monthly(Carbon::create(2026, 1, 1));

        $html = view('reports.pdf', $report)->render();

        $this->assertStringContainsString('Janvier 2026', $html);
        $this->assertStringContainsString('Aucun ticket créé pendant ce mois.', $html);
    }

    // ---------- Feuilles Excel ----------

    public function test_export_has_an_incidents_and_a_statistics_sheet(): void
    {
        $sheets = (new TicketsExport)->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Incidents', $sheets[0]->title());
        $this->assertSame('Statistiques', $sheets[1]->title());
    }

    public function test_incidents_sheet_exports_readable_rows_and_respects_filters(): void
    {
        $this->seedData();

        $all = (new IncidentsSheet)->collection();
        $this->assertCount(4, $all);
        $this->assertSame(count((new IncidentsSheet)->headings()), count($all->first()));

        $row = $all->first(fn ($r) => $r[0] === $this->t['t2']->id);
        $this->assertSame('Critique', $row[3]);
        $this->assertSame('En cours', $row[4]);
        $this->assertSame('Tech Alpha', $row[6]);
        $this->assertSame('10/09/2026', $row[8]);
        $this->assertSame(90, $row[10]);

        $filtered = (new IncidentsSheet(['status' => 'closed']))->collection();
        $this->assertSame([$this->t['t4']->id], $filtered->pluck(0)->all());

        $byTech = (new IncidentsSheet(['technician_id' => $this->techA->id]))->collection();
        $this->assertEqualsCanonicalizing([$this->t['t2']->id, $this->t['t3']->id], $byTech->pluck(0)->all());
    }

    public function test_statistics_sheet_contains_the_indicators_for_the_filtered_tickets(): void
    {
        $this->seedData();

        $value = fn ($rows, string $label) => $rows->first(fn ($r) => $r[0] === $label)[1];

        $all = (new StatisticsSheet)->collection();
        $this->assertSame(4, $value($all, 'Total incidents'));
        $this->assertSame('4 h', $value($all, 'Temps moyen de résolution'));
        $this->assertSame('Toutes les périodes', $value($all, 'Période'));
        $this->assertSame(1, $value($all, 'Critique'));

        $filtered = (new StatisticsSheet(['date_from' => '2026-09-05', 'date_to' => '2026-09-30']))->collection();
        $this->assertSame(2, $value($filtered, 'Total incidents'));
        $this->assertSame('Du 05/09/2026 au 30/09/2026', $value($filtered, 'Période'));
        $this->assertSame('N/A', $value($filtered, 'Temps moyen de résolution'));
    }

    // ---------- Routes ----------

    public function test_reports_page_lists_the_pdf_and_excel_options(): void
    {
        $this->seedData();

        $this->actingAs($this->admin())->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Rapport mensuel')
            ->assertSee('Export Excel')
            ->assertSee('Réseau')
            ->assertSee('Tech Alpha');
    }

    public function test_admin_downloads_the_excel_export(): void
    {
        $this->seedData();

        $response = $this->actingAs($this->admin())
            ->get(route('reports.excel', ['status' => 'closed', 'date_from' => '2026-09-01']))
            ->assertOk();

        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_admin_downloads_the_monthly_pdf(): void
    {
        $this->seedData();

        $response = $this->actingAs($this->admin())
            ->get(route('reports.pdf', ['month' => '2026-09']))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('rapport-mensuel-2026-09.pdf', $response->headers->get('content-disposition'));
    }

    public function test_pdf_defaults_to_the_current_month(): void
    {
        $response = $this->actingAs($this->admin())->get(route('reports.pdf'))->assertOk();

        $this->assertStringContainsString(
            'rapport-mensuel-'.now()->format('Y-m').'.pdf',
            $response->headers->get('content-disposition')
        );
    }

    public function test_report_filters_are_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('reports.excel', ['status' => 'inconnu']))->assertSessionHasErrors('status');
        $this->actingAs($admin)->get(route('reports.excel', ['priority' => 'urgente']))->assertSessionHasErrors('priority');
        $this->actingAs($admin)->get(route('reports.excel', ['category_id' => 9999]))->assertSessionHasErrors('category_id');
        $this->actingAs($admin)->get(route('reports.excel', ['date_from' => '2026-09-10', 'date_to' => '2026-09-01']))
            ->assertSessionHasErrors('date_to');
        $this->actingAs($admin)->get(route('reports.pdf', ['month' => 'septembre']))->assertSessionHasErrors('month');
    }

    public function test_only_admins_can_download_reports(): void
    {
        $this->actingAs(User::factory()->create())->get(route('reports.excel'))->assertForbidden();
        $this->actingAs(User::factory()->technician()->create())->get(route('reports.pdf'))->assertForbidden();
    }
}
