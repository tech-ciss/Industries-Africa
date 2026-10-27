<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Crée 50 tickets dont l'historique est cohérent avec le workflow :
 * un ticket "Nouveau" n'a pas de technicien, un ticket "Assigné" en a un avec une échéance,
 * un ticket "Résolu/Fermé" a une date de résolution postérieure à sa création, etc.
 * Chaque ticket reçoit son historique, ses commentaires et (une fois commencé) du temps passé,
 * avec des dates étalées dans le passé.
 */
class TicketSeeder extends Seeder
{
    /** Répartition des 50 tickets par statut. */
    private const STATUS_PLAN = ['new' => 8, 'assigned' => 8, 'in_progress' => 10, 'resolved' => 8, 'closed' => 16];

    /** Ancienneté de création (en jours, min-max) selon le statut, pour garder des dates plausibles. */
    private const AGE_DAYS = [
        'new' => [0, 14],
        'assigned' => [1, 14],
        'in_progress' => [3, 21],
        'resolved' => [7, 45],
        'closed' => [15, 60],
    ];

    private const PRIORITIES = ['low', 'low', 'normal', 'normal', 'normal', 'normal', 'normal', 'high', 'high', 'critical'];

    private const LOCATIONS = [
        'Bureau 12', 'Salle de réunion A', 'Bâtiment B - 2e étage', 'Open space',
        'Accueil', 'Salle serveur', 'Entrepôt', 'Cafétéria',
    ];

    private const CATALOG = [
        'Climatisation' => [
            ['Climatiseur en panne', 'Le climatiseur ne refroidit plus et affiche un code erreur sur la télécommande.'],
            ['Fuite d\'eau sur le split', 'De l\'eau goutte depuis l\'unité intérieure et mouille le sol.'],
            ['Climatisation bruyante', 'Un bruit anormal de vibration provient de l\'unité extérieure depuis plusieurs jours.'],
            ['Température trop élevée', 'La pièce reste à plus de 30 degrés malgré la climatisation allumée.'],
        ],
        'Electricité' => [
            ['Coupure de courant', 'Les prises de la zone ne sont plus alimentées depuis ce matin.'],
            ['Néons hors service', 'Plusieurs néons du couloir ne s\'allument plus et laissent la zone dans le noir.'],
            ['Disjoncteur qui saute', 'Le disjoncteur se déclenche dès que plusieurs ordinateurs sont branchés.'],
            ['Prise électrique endommagée', 'Une prise murale est noircie et dégage une odeur de brûlé.'],
        ],
        'Réseau' => [
            ['Pas de connexion internet', 'Aucun poste de la zone ne parvient à se connecter au réseau depuis ce matin.'],
            ['Wifi instable', 'Le wifi se coupe toutes les dix minutes et ralentit les visioconférences.'],
            ['Prise réseau inactive', 'La prise murale du poste ne délivre plus aucune connexion.'],
            ['Switch en panne', 'Le voyant du switch est éteint et les postes raccordés sont hors ligne.'],
        ],
        'Plomberie' => [
            ['Fuite sous le lavabo', 'Une fuite continue sous le lavabo des sanitaires inonde le meuble.'],
            ['WC bouchés', 'Les toilettes sont bouchées et l\'eau remonte dans la cuvette.'],
            ['Chasse d\'eau défectueuse', 'La chasse d\'eau coule en permanence et fait grimper la consommation.'],
            ['Robinet qui goutte', 'Le robinet de la cuisine goutte en continu même complètement fermé.'],
        ],
        'Imprimante' => [
            ['Imprimante bloquée', 'L\'imprimante affiche un bourrage papier alors que le bac est vide.'],
            ['Impressions pâles', 'Les impressions sortent très pâles malgré le remplacement de la cartouche.'],
            ['Imprimante hors ligne', 'L\'imprimante apparaît hors ligne pour tous les postes du service.'],
            ['Scanner inopérant', 'La fonction scan de l\'imprimante multifonction ne fonctionne plus.'],
        ],
    ];

    private const EMPLOYEE_COMMENTS = [
        'Le problème persiste depuis ce matin, merci de passer dès que possible.',
        'L\'incident est toujours en cours et bloque une partie de l\'équipe.',
        'Je suis sur place toute la journée si vous avez besoin d\'accéder au local.',
        'Pouvez-vous me donner une estimation du délai d\'intervention ?',
        'Le dysfonctionnement est apparu juste après la coupure de la nuit dernière.',
    ];

    private const TECHNICIAN_COMMENTS = [
        'Je passe sur place cet après-midi pour un premier diagnostic.',
        'Diagnostic effectué : une pièce doit être remplacée, je la commande.',
        'Pouvez-vous me confirmer l\'emplacement exact de l\'équipement ?',
        'J\'ai besoin d\'accéder au local technique, merci de prévenir le gardien.',
        'Intervention en cours, je vous tiens informé de l\'avancement.',
    ];

    private const RESOLUTION_COMMENTS = [
        'Intervention terminée, l\'équipement fonctionne normalement. Merci de confirmer.',
        'Pièce remplacée et tests effectués : tout est rentré dans l\'ordre.',
        'Problème corrigé. N\'hésitez pas à rouvrir une demande si le souci réapparaît.',
    ];

    private const TIME_NOTES = [
        'Diagnostic sur place', 'Remplacement de pièce', 'Tests de fonctionnement', 'Déplacement et prise en charge',
    ];

    public function run(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $technicians = User::where('role', 'technician')->where('status', 'active')->get();
        $employees = User::where('role', 'employee')->where('status', 'active')->get();
        $categories = Category::all()->keyBy('name');

        $statuses = [];
        foreach (self::STATUS_PLAN as $status => $count) {
            $statuses = array_merge($statuses, array_fill(0, $count, $status));
        }

        foreach (fake()->shuffle($statuses) as $status) {
            $this->seedTicket($status, $admin, $technicians->all(), $employees->all(), $categories);
        }
    }

    private function seedTicket(string $status, User $admin, array $technicians, array $employees, $categories): void
    {
        [$minAge, $maxAge] = self::AGE_DAYS[$status];
        $createdAt = now()->subDays(fake()->numberBetween($minAge, $maxAge))->subMinutes(fake()->numberBetween(30, 600)); // jamais "à l'instant" : tous les événements restent dans le passé

        $categoryName = fake()->randomElement(array_keys(self::CATALOG));
        [$title, $description] = fake()->randomElement(self::CATALOG[$categoryName]);
        $location = fake()->randomElement(self::LOCATIONS);
        $requester = fake()->randomElement($employees);

        // Chronologie : on calcule toutes les dates avant de créer le ticket.
        $events = [[$createdAt, $requester->id, 'created', 'Ticket créé.']];
        $technician = $deadline = $assignedAt = $startedAt = $resolvedAt = $closedAt = null;

        if ($status !== 'new') {
            $technician = fake()->randomElement($technicians);
            $assignedAt = $createdAt->copy()->addMinutes(fake()->numberBetween(60, 720));
            $deadline = $assignedAt->copy()->addDays(fake()->numberBetween(2, 10))->startOfDay();
            $events[] = [$assignedAt, $admin->id, 'assigned',
                "Assigné à {$technician->full_name} (échéance : {$deadline->format('d/m/Y')})."];
        }
        if (in_array($status, ['in_progress', 'resolved', 'closed'], true)) {
            $startedAt = $assignedAt->copy()->addMinutes(fake()->numberBetween(30, 720));
            $events[] = [$startedAt, $technician->id, 'status_changed', 'Statut changé : Assigné → En cours.'];
        }
        if (in_array($status, ['resolved', 'closed'], true)) {
            $resolvedAt = $startedAt->copy()->addMinutes(fake()->numberBetween(90, 4320));
            $events[] = [$resolvedAt, $technician->id, 'status_changed', 'Statut changé : En cours → Résolu.'];
        }
        if ($status === 'closed') {
            $closedAt = $resolvedAt->copy()->addMinutes(fake()->numberBetween(60, 2880));
            $events[] = [$closedAt, $admin->id, 'status_changed', 'Statut changé : Résolu → Fermé.'];
        }

        $end = $closedAt ?? $resolvedAt ?? now();

        $timeRows = $startedAt ? $this->timeEntries($technician, $startedAt, $resolvedAt ?? now(), $events) : [];
        $commentRows = $this->comments($status, $requester, $technician, $createdAt, $assignedAt, $resolvedAt, $end, $events);

        usort($events, fn ($a, $b) => $a[0]->timestamp <=> $b[0]->timestamp);
        $lastEvent = end($events)[0];

        $ticket = Ticket::unguarded(fn () => Ticket::create([
            'title' => "{$title} - {$location}",
            'description' => "{$description} Lieu : {$location}.",
            'priority' => fake()->randomElement(self::PRIORITIES),
            'status' => $status,
            'user_id' => $requester->id,
            'category_id' => $categories[$categoryName]->id,
            'assigned_to' => $technician?->id,
            'deadline' => $deadline,
            'resolved_at' => $resolvedAt,
            'created_at' => $createdAt,
            'updated_at' => $lastEvent,
        ]));

        Activity::insert(array_map(fn ($e) => [
            'ticket_id' => $ticket->id,
            'user_id' => $e[1],
            'action' => $e[2],
            'description' => $e[3],
            'created_at' => $e[0]->toDateTimeString(),
            'updated_at' => $e[0]->toDateTimeString(),
        ], $events));

        if ($commentRows) {
            Comment::insert(array_map(fn ($c) => [
                'ticket_id' => $ticket->id,
                'user_id' => $c['user_id'],
                'message' => $c['message'],
                'attachment' => null,
                'created_at' => $c['at']->toDateTimeString(),
                'updated_at' => $c['at']->toDateTimeString(),
            ], $commentRows));
        }

        if ($timeRows) {
            TimeEntry::insert(array_map(fn ($t) => [
                'ticket_id' => $ticket->id,
                'user_id' => $t['user_id'],
                'minutes' => $t['minutes'],
                'note' => $t['note'],
                'created_at' => $t['at']->toDateTimeString(),
                'updated_at' => $t['at']->toDateTimeString(),
            ], $timeRows));
        }
    }

    /** 1 à 3 saisies de temps entre le début des travaux et la résolution (ou maintenant). */
    private function timeEntries(User $technician, Carbon $from, Carbon $to, array &$events): array
    {
        $span = max(1, (int) abs($from->diffInMinutes($to)));
        $rows = [];

        for ($i = 0, $n = fake()->numberBetween(1, 3); $i < $n; $i++) {
            $minutes = fake()->randomElement([15, 30, 45, 60, 90, 120, 180, 240]);
            $at = $from->copy()->addMinutes(fake()->numberBetween(1, $span));

            $rows[] = [
                'user_id' => $technician->id,
                'minutes' => $minutes,
                'note' => fake()->boolean(60) ? fake()->randomElement(self::TIME_NOTES) : null,
                'at' => $at,
            ];
            $events[] = [$at, $technician->id, 'time_logged', TimeEntry::format($minutes).' enregistrées.'];
        }

        return $rows;
    }

    /**
     * Commentaires triés dans le temps : le technicien ne commente qu'après son affectation,
     * les tickets résolus ont un dernier commentaire de clôture du technicien.
     */
    private function comments(string $status, User $requester, ?User $technician, Carbon $createdAt, ?Carbon $assignedAt, ?Carbon $resolvedAt, Carbon $end, array &$events): array
    {
        $count = match ($status) {
            'new' => fake()->numberBetween(0, 1),
            'assigned' => fake()->numberBetween(0, 2),
            default => fake()->numberBetween(1, 4),
        };

        $from = $createdAt->copy()->addMinutes(10);
        $span = max(1, (int) abs($from->diffInMinutes($resolvedAt ?? $end)));

        $times = [];
        for ($i = 0; $i < $count; $i++) {
            $times[] = $from->copy()->addMinutes(fake()->numberBetween(0, $span));
        }
        usort($times, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        $rows = [];
        foreach ($times as $i => $at) {
            $byTechnician = $technician && $assignedAt && $at->gt($assignedAt) && $i % 2 === 0;
            $author = $byTechnician ? $technician : $requester;
            $rows[] = [
                'user_id' => $author->id,
                'message' => fake()->randomElement($byTechnician ? self::TECHNICIAN_COMMENTS : self::EMPLOYEE_COMMENTS),
                'at' => $at,
            ];
        }

        if ($resolvedAt) {
            $rows[] = [
                'user_id' => $technician->id,
                'message' => fake()->randomElement(self::RESOLUTION_COMMENTS),
                'at' => $resolvedAt->copy(),
            ];
        }

        foreach ($rows as $row) {
            $events[] = [$row['at'], $row['user_id'], 'commented', 'Commentaire ajouté.'];
        }

        return $rows;
    }
}
