<?php

declare(strict_types=1);

/**
 * RFC 0017, rollout step 5 — the scanner canary. NOT FOR MERGE.
 *
 * Round 2 of the design measured this exact fixture against this exact command and
 * recorded `4 findings (4 blocking)`, exit 1. It is reproduced here shape for shape,
 * because the first attempt at this canary did not reproduce it: that version routed the
 * request value through an intermediate variable, and a pattern-based rule does not
 * follow an assignment the way a taint-mode rule does. Zero findings there is not
 * evidence about the scanner — it is evidence about a fixture nobody had measured.
 *
 * Three shapes, the ones `sql-builder` and `http-input` really have:
 *   1. a superglobal concatenated into SQL
 *   2. `eval` on request input
 *   3. a shell call on request input
 *
 * It lives outside `src/` and `tests/` on purpose. Every other verb here looks only at
 * those two directories — phpstan.neon.dist, .php-cs-fixer.dist.php, phpunit.xml and
 * infection.json5.dist all say so — while semgrep scans `.`. A fixture inside `src/`
 * would redden `analyse` and `coverage` first and the scanner step would never run,
 * which is how three canaries were masked during the 2026-08-12 campaign.
 *
 * @param \PDO $connection
 */
function canary(\PDO $connection): void
{
    // 1. Request input concatenated straight into a query.
    $connection->query('SELECT * FROM users WHERE id = ' . $_GET['id']);

    // 2. Request input evaluated as code, undivided — no intermediate variable.
    eval($_POST['cmd']);

    // 3. Request input handed to a shell.
    system('ping -c 1 ' . $_GET['host']);
}
