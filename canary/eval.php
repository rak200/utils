<?php

declare(strict_types=1);

/**
 * RFC 0017, rollout step 5 — the scanner canary. NOT FOR MERGE.
 *
 * A deliberately vulnerable fixture, planted so that `Scanner findings block the merge`
 * can be observed failing. semgrep's `p/security-audit` pack carries `eval-use`, which
 * matches the shape below; `p/php` alone does not, which is the regression this canary
 * exists to prove is fixed.
 *
 * It lives outside `src/` and `tests/` on purpose. Every other verb in this repository
 * looks only at those two directories — phpstan.neon.dist, .php-cs-fixer.dist.php,
 * phpunit.xml and infection.json5.dist all say so — while semgrep scans `.`. A fixture
 * inside `src/` would redden `analyse` and `coverage` first, and the scanner step would
 * never run: that is exactly how three canaries were masked during the 2026-08-12
 * campaign, and a canary that fails an earlier gate has tested the earlier gate.
 */

$payload = $_POST['cmd'];

eval($payload);
