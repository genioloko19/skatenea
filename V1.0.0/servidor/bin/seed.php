<?php

declare(strict_types=1);

use Skatenea\Auth\PasswordService;
use Skatenea\Core\Config;
use Skatenea\Core\Database;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Payment\PaymentRepositoryInterface;
use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

try {
    /** @var Skatenea\Core\App $app */
    $app = require dirname(__DIR__) . '/config/bootstrap.php';
    $c = $app->container();
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo iniciar: {$e->getMessage()}\n");
    exit(1);
}

$config = $c->get(Config::class);
$database = $c->get(Database::class);
$users = $c->get(UserRepositoryInterface::class);
$buyers = $c->get(BuyerRepositoryInterface::class);
$raffles = $c->get(RaffleRepositoryInterface::class);
$tickets = $c->get(TicketRepositoryInterface::class);
$payments = $c->get(PaymentRepositoryInterface::class);
$passwords = $c->get(PasswordService::class);

$now = gmdate('Y-m-d H:i:s');
$seed = [
    [
        'role' => User::ROLE_SUPER_ADMIN,
        'full_name' => 'Super Administrador',
        'email' => $config->string('SEED_SUPER_ADMIN_EMAIL', 'admin@skatenea.local'),
        'password' => $config->string('SEED_SUPER_ADMIN_PASSWORD', 'Admin#2026!'),
        'phone' => '3000000001',
    ],
    [
        'role' => User::ROLE_LEADER,
        'full_name' => 'Líder de Rifas',
        'email' => $config->string('SEED_LEADER_EMAIL', 'lider@skatenea.local'),
        'password' => $config->string('SEED_LEADER_PASSWORD', 'Lider#2026!'),
        'phone' => '3000000002',
    ],
    [
        'role' => User::ROLE_SELLER,
        'full_name' => 'Vendedor Demo',
        'email' => $config->string('SEED_SELLER_EMAIL', 'vendedor@skatenea.local'),
        'password' => $config->string('SEED_SELLER_PASSWORD', 'Vendedor#2026!'),
        'phone' => '3000000003',
    ],
    [
        'role' => User::ROLE_BUYER,
        'full_name' => 'Comprador Demo',
        'email' => $config->string('SEED_BUYER_EMAIL', 'comprador@skatenea.local'),
        'password' => $config->string('SEED_BUYER_PASSWORD', 'Comprador#2026!'),
        'phone' => '3000000004',
    ],
];

$created = [];
foreach ($seed as $item) {
    $existing = $users->findByEmail($item['email']);
    if ($existing !== null) {
        $created[$item['role']] = $existing;
        continue;
    }

    $user = $users->create([
        'id' => Uuid::v4(),
        'email' => mb_strtolower($item['email']),
        'password_hash' => $passwords->hash($item['password']),
        'full_name' => $item['full_name'],
        'role' => $item['role'],
        'status' => User::STATUS_ACTIVE,
        'phone' => $item['phone'],
        'document_type' => 'CC',
        'document_number' => '100000000' . count($created),
        'must_change_password' => 0,
        'last_login_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $created[$item['role']] = $user;
    echo "Usuario creado: {$item['email']} ({$item['role']})\n";
}

$superAdmin = $created[User::ROLE_SUPER_ADMIN];
$leader = $created[User::ROLE_LEADER];
$seller = $created[User::ROLE_SELLER];
$buyerUser = $created[User::ROLE_BUYER];

$buyer = $buyers->findByUserId($buyerUser->id);
if ($buyer === null) {
    $buyer = $buyers->create([
        'id' => Uuid::v4(),
        'full_name' => $buyerUser->fullName,
        'email' => $buyerUser->email,
        'phone' => (string) $buyerUser->phone,
        'document_type' => 'CC',
        'document_number' => '900000001',
        'user_id' => $buyerUser->id,
        'created_by' => $seller->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    echo "Comprador creado para el usuario {$buyerUser->email}\n";
}

$raffle = $raffles->findByCode('DEMO-001');
if ($raffle === null) {
    $raffle = $raffles->create([
        'id' => Uuid::v4(),
        'code' => 'DEMO-001',
        'name' => 'Rifa Demo SKATENEA',
        'description' => 'Rifa de demostración con 100 boletas. Datos ficticios.',
        'prize' => 'Bono de demostración',
        'ticket_price' => '10000.00',
        'total_tickets' => 100,
        'status' => Raffle::STATUS_ACTIVE,
        'created_by' => $leader->id,
        'approved_by' => $superAdmin->id,
        'approved_at' => $now,
        'rejection_reason' => null,
        'start_at' => gmdate('Y-m-d H:i:s', time() - 3600),
        'end_at' => gmdate('Y-m-d H:i:s', time() + 86400 * 30),
        'draw_seed_hash' => null,
        'draw_seed' => null,
        'winner_ticket_id' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $raffles->assignSeller($raffle->id, $seller->id, $leader->id, $now);
    $raffles->recordApproval([
        'id' => Uuid::v4(),
        'raffle_id' => $raffle->id,
        'actor_id' => $leader->id,
        'action' => 'submitted',
        'reason' => null,
        'created_at' => $now,
    ]);
    $raffles->recordApproval([
        'id' => Uuid::v4(),
        'raffle_id' => $raffle->id,
        'actor_id' => $superAdmin->id,
        'action' => 'approved',
        'reason' => 'Aprobación de demostración',
        'created_at' => $now,
    ]);
    echo "Rifa DEMO-001 creada.\n";
}

$stats = $tickets->countByStatus($raffle->id);
if (($stats['total'] ?? 0) === 0) {
    $rows = [];
    for ($number = 1; $number <= $raffle->totalTickets; $number++) {
        $rows[] = [
            'id' => Uuid::v4(),
            'raffle_id' => $raffle->id,
            'number' => $number,
            'seller_id' => $number <= 50 ? $seller->id : null,
            'buyer_id' => null,
            'status' => Ticket::STATUS_AVAILABLE,
            'price' => $raffle->ticketPrice,
            'token' => bin2hex(random_bytes(32)),
            'reserved_until' => null,
            'reserved_at' => null,
            'paid_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    $database->transaction(fn (): int => $tickets->createMany($rows));

    // Deja una boleta pagada para que el dashboard tenga datos.
    $first = $tickets->findByRaffleAndNumber($raffle->id, 1);
    if ($first !== null) {
        $tickets->transition($first->id, Ticket::STATUS_AVAILABLE, [
            'status' => Ticket::STATUS_RESERVED,
            'buyer_id' => $buyer->id,
            'reserved_at' => $now,
            'reserved_until' => gmdate('Y-m-d H:i:s', time() + 86400),
            'updated_at' => $now,
        ]);
        $tickets->transition($first->id, Ticket::STATUS_RESERVED, [
            'status' => Ticket::STATUS_PAID,
            'paid_at' => $now,
            'updated_at' => $now,
        ]);
        $payments->create([
            'id' => Uuid::v4(),
            'ticket_id' => $first->id,
            'amount' => $raffle->ticketPrice,
            'method' => 'cash',
            'reference' => 'DEMO-SEED',
            'status' => 'confirmed',
            'confirmed_by' => $seller->id,
            'confirmed_at' => $now,
            'created_at' => $now,
        ]);
    }
    echo "100 boletas generadas (50 asignadas al vendedor).\n";
}

echo "\n=== Credenciales de desarrollo (no usar en producción) ===\n";
echo "Super admin: {$seed[0]['email']} / {$seed[0]['password']}\n";
echo "Líder:      {$seed[1]['email']} / {$seed[1]['password']}\n";
echo "Vendedor:   {$seed[2]['email']} / {$seed[2]['password']}\n";
echo "Comprador:  {$seed[3]['email']} / {$seed[3]['password']}\n";
