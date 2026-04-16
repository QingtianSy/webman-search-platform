#!/bin/sh
set -eu

ROOT=${1:-.}
ROUTE_FILE="$ROOT/backend/config/route.php"
BACKEND_DIR="$ROOT/backend"

php -r '
$f = $argv[1];
$backend = $argv[2];
$s = file_get_contents($f);
preg_match_all("/^use\\s+([^;]+);/m", $s, $m);
$miss = 0;
foreach ($m[1] as $u) {
    $u = trim($u);
    if (str_starts_with($u, "app\\\\controller") || str_starts_with($u, "app\\\\process")) {
        $rel = $backend . "/app/" . str_replace("\\\\", "/", preg_replace("/^app\\\\/", "", $u)) . ".php";
        if (!file_exists($rel)) {
            fwrite(STDERR, "MISS $u => $rel\n");
            $miss = 1;
        }
    }
}
if ($miss) {
    exit(1);
}
echo "route targets ok\n";
' "$ROUTE_FILE" "$BACKEND_DIR"
