<?php
// debug_invoice.php
namespace App\Controllers;
use CodeIgniter\Controller;
use App\Repositories\Invoices\InvoiceRepository;

// Bootstrap CodeIgniter
require_once __DIR__ . '/backend-ci/public/index.php';

// We can't easily bootstrap full app context from CLI outside spark 
// but we can query DB if we use spark runner or just raw PDO.

// Valid approach: Create a command or controller method we can trigger via spark or curl
// But easier: use `php spark eval` if available? CI4 doesn't have native eval.

// Let's create a temporary Test Controller inside the app structure?
// No, I'll just look at the DB directly using sqlite3 or mysql client?
// DB is MySQL.

// I will create a script that connects to DB using credentials from .env
// But parsing .env is annoying.

// Best way: Create a temporary route in Routes.php
// No, user is watching.

// I will use `php spark db:table invoices --limit 1`? 
// CI4 `db:table` command exists? Yes usually.

// Let's try listing invoices via spark.
