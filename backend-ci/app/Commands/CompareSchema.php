<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class CompareSchema extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'compare:schema';
    protected $description = 'Compare schema between default (prod) and tests databases.';

    public function run(array $params)
    {
        $prod = Database::connect('default');
        $test = Database::connect('tests');

        CLI::write("Comparing '{$prod->database}' (Prod) vs '{$test->database}' (Test)...", 'yellow');

        $prodTables = $prod->listTables();
        $testTables = $test->listTables();

        // 1. Compare Table Existence
        $onlyInProd = array_diff($prodTables, $testTables);
        $onlyInTest = array_diff($testTables, $prodTables);

        if ($onlyInProd) {
            CLI::write("Tables only in Prod:", 'red');
            foreach ($onlyInProd as $t) CLI::write(" - $t");
        }
        if ($onlyInTest) {
            CLI::write("Tables only in Test:", 'red');
            foreach ($onlyInTest as $t) CLI::write(" - $t");
        }

        // 2. Compare Columns
        $commonTables = array_intersect($prodTables, $testTables);
        foreach ($commonTables as $table) {
            $prodFields = $prod->getFieldData($table);
            $testFields = $test->getFieldData($table);

            $prodCols = [];
            foreach ($prodFields as $f) $prodCols[$f->name] = $f;

            $testCols = [];
            foreach ($testFields as $f) $testCols[$f->name] = $f;

            // Missing columns
            $missingInTest = array_diff(array_keys($prodCols), array_keys($testCols));
            if ($missingInTest) {
                CLI::write("Table '$table': Missing columns in Test:", 'red');
                foreach ($missingInTest as $c) CLI::write(" - $c");
            }

            $missingInProd = array_diff(array_keys($testCols), array_keys($prodCols));
            if ($missingInProd) {
                CLI::write("Table '$table': Missing columns in Prod:", 'red');
                foreach ($missingInProd as $c) CLI::write(" - $c");
            }

            // Type mismatches
            foreach (array_intersect(array_keys($prodCols), array_keys($testCols)) as $col) {
                $p = $prodCols[$col];
                $t = $testCols[$col];

                // Normalize types (e.g. int vs integer)
                $pType = strtolower($p->type);
                $tType = strtolower($t->type);
                
                if ($pType !== $tType || $p->max_length !== $t->max_length) {
                     CLI::write("Table '$table' Column '$col' mismatch:", 'yellow');
                     CLI::write("  Prod: $pType({$p->max_length})", 'white');
                     CLI::write("  Test: $tType({$t->max_length})", 'white');
                }
            }
        }

        CLI::write("Comparison complete.", 'green');
    }
}
