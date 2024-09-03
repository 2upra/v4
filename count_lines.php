<?php
header('Content-Type: application/json');

$theme_path = '/var/www/html/wp-content/themes/records-2upra';

$command = "git -C $theme_path ls-files | xargs wc -l";
$output = shell_exec($command);

if ($output === null) {
    echo json_encode(['error' => 'Failed to execute command']);
    exit;
}

// Log output for debugging
file_put_contents('/tmp/debug.log', $output);

// Log the exact command used
file_put_contents('/tmp/debug_command.log', $command);

$lines = 0;
if ($output) {
    $lines = array_sum(array_map('intval', preg_grep('/^\d+/', explode("\n", $output))));
}

echo json_encode(['lines_of_code' => $lines]);
?>