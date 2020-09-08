<?php
/* MySQL-Tuner-PHP - Version 0.3
 */
$physicalMemory = 8 * 1024 * 1024 * 1024;
$preferredQueryTime = 5;

function human_readable_bytes($number) {
    if ($number >= 1024 * 1024 * 1024) {
        return round($number / (1024 * 1024 * 1024), 1) . " GB";
    }
    elseif ($number >= 1024 * 1024) {
        return round($number / (1024 * 1024), 1) . " MB";
    }
    elseif ($number >= 1024) {
        return round($number / (1024), 1) . " KB";
    }
    else {
        return $number . " bytes";
    }
}

function human_readable_time($seconds) {
    return floor($seconds / 86400) . " days, " . ($seconds / 3600 % 24) . " hrs, " . ($seconds / 60 % 60) . " min";
}

function human_readable_comma_enum($string) {
    $parts = explode(",", $string);
    $list = "<ul>";
    foreach ($parts as $part) {
        $list .= "<li>" . trim($part) . "</li>";
    }
    $list .= "</ul>";
    return $list;
}

function percentage($value, $total = 0) {
    if ($total == 0) return 100;
    return ($value * 100 / $total);
}

function alert_check() {
    return "<svg width=\"1em\" height=\"1em\" viewBox=\"0 0 16 16\" class=\"bi bi-check float-right mt-1 text-success\" fill=\"currentColor\" xmlns=\"http://www.w3.org/2000/svg\">\n"
        . "<path fill-rule=\"evenodd\" d=\"M10.97 4.97a.75.75 0 0 1 1.071 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.236.236 0 0 1 .02-.022z\"/>\n"
        . "</svg>";
}

function alert_error() {
    return "<svg width=\"1em\" height=\"1em\" viewBox=\"0 0 16 16\" class=\"bi bi-exclamation-octagon-fill float-right mt-1 text-danger\" fill=\"currentColor\" xmlns=\"http://www.w3.org/2000/svg\">\n"
        . "<path fill-rule=\"evenodd\" d=\"M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z\"/>\n"
        . "</svg>";
}

function alert_info() {
    return "<svg width=\"1em\" height=\"1em\" viewBox=\"0 0 16 16\" class=\"bi bi-info-circle-fill float-right mt-1 text-info\" fill=\"currentColor\" xmlns=\"http://www.w3.org/2000/svg\">\n"
        . "<path fill-rule=\"evenodd\" d=\"M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM8 5.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2z\"/>\n"
        . "</svg>";
}

function alert_warning() {
    return "<svg width=\"1.0625em\" height=\"1em\" viewBox=\"0 0 17 16\" class=\"bi bi-exclamation-triangle-fill float-right mt-1 text-warning\" fill=\"currentColor\" xmlns=\"http://www.w3.org/2000/svg\">\n"
        . "<path fill-rule=\"evenodd\" d=\"M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 5zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z\"/>\n"
        . "</svg>";
}

$user = getenv("MTP_USER");
$pass = getenv("MTP_PASS");
$host = getenv("MTP_HOST");
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/* Server version */
$stmt = $pdo->query("SELECT VERSION() AS version");
$version = $stmt->fetch()['version'];
$versionParts = explode(".", $version);
$majorVersion = $versionParts[0] . "." . $versionParts[1];

/* Global status */
$globalStatus = [];
$stmt = $pdo->query('SHOW GLOBAL STATUS');
while ($row = $stmt->fetch()) {
    $globalStatus[$row['Variable_name']] = $row['Value'];
}

/* Global variables */
$globalVariables = [];
$stmt = $pdo->query('SHOW GLOBAL VARIABLES');
while ($row = $stmt->fetch()) {
    $globalVariables[$row['Variable_name']] = $row['Value'];
}

/* Engines */
$engines = [];
$stmt = $pdo->query('SELECT Engine, Support, Comment, Transactions, XA, Savepoints FROM information_schema.ENGINES ORDER BY Engine ASC');
while ($row = $stmt->fetch()) {
    $engines[$row['Engine']] = [
        'Support' => $row['Support'],
        'Comment' => $row['Comment'],
        'Transactions' => $row['Transactions'],
        'XA' => $row['XA'],
        'Savepoints' => $row['Savepoints'],
    ];
}

$logError = $globalVariables['log_error'];

$questions = $globalStatus['Questions'];
$uptime = $globalStatus['Uptime'];
$avgQps = $questions / $uptime;
$threadsConnected = $globalStatus['Threads_connected'];

/* Slow queries */
$slowQueries = $globalStatus['Slow_queries'];
$logOutput = $globalVariables['log_output'];
$logQueriesNotUsingIndexes = $globalVariables['log_queries_not_using_indexes'];
$logSlowAdminStatements = $globalVariables['log_slow_admin_statements'];
$logSlowDisabledStatements = $globalVariables['log_slow_disabled_statements'];
$logSlowFilter = $globalVariables['log_slow_filter'];
$logSlowRateLimit = $globalVariables['log_slow_rate_limit'];
$logSlowVerbosity = $globalVariables['log_slow_verbosity'];
$longQueryTime = $globalVariables['long_query_time'];
$minExaminedRowLimit = $globalVariables['min_examined_row_limit'];
$slowQueryLog = $globalVariables['slow_query_log'];
$slowQueryLogFile = $globalVariables['slow_query_log_file'];
$slowQueriesPct = $slowQueryLog * 100 / $questions;

/* Binary log */
$logBin = $globalVariables['log_bin'];
$maxBinlogSize = $globalVariables['max_binlog_size'];
$expireLogsDays = $globalVariables['expire_logs_days'];
$syncBinlog = $globalVariables['sync_binlog'];

/* Threads */
$threadsCreated = $globalStatus['Threads_created'];
$threadsCached = $globalStatus['Threads_cached'];
$threadHandling = $globalVariables['thread_handling'];
$threadCacheSize = $globalVariables['thread_cache_size'];
$historicThreadsPerSec = $threadsCreated / $uptime;

/* Used connections */
$maxConnections = $globalVariables['max_connections'];
$maxUsedConnections = $globalStatus['Max_used_connections'];
$threadsConnected = $globalStatus['Threads_connected'];
$maxConnectionsUsage = ($maxUsedConnections * 100 / $maxConnections);
$abortedConnects = $globalStatus['Aborted_connects'];
$connections = $globalStatus['Connections'];
$abortedConnectsPct = percentage($abortedConnects, $connections);

/* InnoDB */
$innodbBufferPoolSize = $globalVariables['innodb_buffer_pool_size'];
$innodbFilePerTable = $globalVariables['innodb_file_per_table'];
$innodbFastShutdown = $globalVariables['innodb_fast_shutdown'];
$innodbFlushLogAtTrxCommit = $globalVariables['innodb_flush_log_at_trx_commit'];
$innodbLogBufferSize = $globalVariables['innodb_log_buffer_size'];
$innodbLogFileSize = $globalVariables['innodb_log_file_size'];
$innodbLogFilesInGroup = $globalVariables['innodb_log_files_in_group'];
$innodbBufferPoolBytesData = $globalStatus['Innodb_buffer_pool_bytes_data'];
$innodbBufferPoolBytesDirty = $globalStatus['Innodb_buffer_pool_bytes_dirty'];
$innodbBufferPoolReads = $globalStatus['Innodb_buffer_pool_reads'];
$innodbBufferPoolReadRequests = $globalStatus['Innodb_buffer_pool_read_requests'];
$innodbBufferPoolWaitFree = $globalStatus['Innodb_buffer_pool_wait_free'];
$innodbDataRead = $globalStatus['Innodb_data_read'];
$innodbDataReads = $globalStatus['Innodb_data_reads'];
$innodbDataWrites = $globalStatus['Innodb_data_writes'];
$innodbDataWritten = $globalStatus['Innodb_data_written'];
$innodbBufferPoolReadRatio = $innodbBufferPoolReads * 100 / $innodbBufferPoolReadRequests;

/* InnoDB Index Length */
$innodbIndexLength = 0;
$stmt = $pdo->query("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS index_length from information_schema.TABLES where ENGINE='InnoDB'");
$innodbIndexLength = $stmt->fetch()['index_length'];

/* InnoDB Data Length */
$innodbDataLength = 0;
$stmt = $pdo->query("SELECT IFNULL(SUM(DATA_LENGTH),0) AS data_length from information_schema.TABLES where ENGINE='InnoDB'");
$innodbDataLength = $stmt->fetch()['data_length'];


if ($innodbIndexLength > 0) {
    $innodbBufferPoolPagesData = $globalStatus['Innodb_buffer_pool_pages_data'];
    $innodbBufferPoolPagesMisc = $globalStatus['Innodb_buffer_pool_pages_misc'];
    $innodbBufferPoolPagesFree = $globalStatus['Innodb_buffer_pool_pages_free'];
    $innodbBufferPoolPagesTotal = $globalStatus['Innodb_buffer_pool_pages_total'];
    $innodbBufferPoolReadAheadSeq = $globalStatus['Innodb_buffer_pool_read_ahead_seq'];
    $innodbOsLogPendingFsyncs = $globalStatus['Innodb_os_log_pending_fsyncs'];
    $innodbOsLogPendingWrites = $globalStatus['Innodb_os_log_pending_writes'];
    $innodbLogWaits = $globalStatus['Innodb_log_waits'];
    $innodbRowLockTime = $globalStatus['Innodb_row_lock_time'];
    $innodbRowLockWaits = $globalStatus['Innodb_row_lock_waits'];

    $innodbBufferPoolFreePct = $innodbBufferPoolPagesFree * 100 / $innodbBufferPoolPagesTotal;
}

/* Memory usage */
$readBufferSize = $globalVariables['read_buffer_size'];
$readRndBufferSize = $globalVariables['read_rnd_buffer_size'];
$sortBufferSize = $globalVariables['sort_buffer_size'];
$threadStack = $globalVariables['thread_stack'];
$maxConnections = $globalVariables['max_connections'];
$netBufferLength = $globalVariables['net_buffer_length'];
$joinBufferSize = $globalVariables['join_buffer_size'];
$tmpTableSize = $globalVariables['tmp_table_size'];
$ariaPagecacheBufferSize = $globalVariables['aria_pagecache_buffer_size'];
$maxHeapTableSize = $globalVariables['max_heap_table_size'];
$logBin = $globalVariables['log_bin'];
$maxUsedConnections = $globalStatus['Max_used_connections'];

if ($logBin == "ON") {
    $binlogCacheSize = $globalVariables['binlog_cache_size'];
}
else {
    $binlogCacheSize = 0;
}
if ($maxHeapTableSize < $tmpTableSize) {
    $effectiveTmpTableSize = $maxHeapTableSize;
}
else {
    $effectiveTmpTableSize = $tmpTableSize;
}

$perThreadBufferSize = $readBufferSize + $readRndBufferSize + $sortBufferSize + $threadStack + $netBufferLength + $joinBufferSize + $binlogCacheSize;
$perThreadBuffers = $perThreadBufferSize * $maxConnections;
$perThreadMaxBuffers = $perThreadBufferSize * $maxUsedConnections;

$tmpTableSize = $globalVariables['tmp_table_size'];
$maxHeapTableSize = $globalVariables['max_heap_table_size'];

$innodbBufferPoolSize = $globalVariables['innodb_buffer_pool_size'];
if (empty($innodbBufferPoolSize)) $innodbBufferPoolSize = 0;

$innodbAdditionalMemPoolSize = $globalVariables['innodb_additional_mem_pool_size'];
if (empty($innodbAdditionalMemPoolSize)) $innodbAdditionalMemPoolSize = 0;

$innodbLogBufferSize = $globalVariables['innodb_log_buffer_size'];
if (empty($innodbLogBufferSize)) $innodbLogBufferSize = 0;

$keyBufferSize = $globalVariables['key_buffer_size'];

$queryCacheSize = $globalVariables['query_cache_size'];
if (empty($queryCacheSize)) $queryCacheSize = 0;

$globalBufferSize = $tmpTableSize + $innodbBufferPoolSize + $innodbAdditionalMemPoolSize + $innodbLogBufferSize + $keyBufferSize + $queryCacheSize + $ariaPagecacheBufferSize;

$maxMemory = $globalBufferSize + $perThreadMaxBuffers;
$totalMemory = $globalBufferSize + $perThreadBuffers;

$pctOfSysMem = $totalMemory * 100 / $physicalMemory;

/* Key buffer size */
$keyReadRequests = $globalStatus['Key_read_requests'];
$keyReads = $globalStatus['Key_reads'];
$keyBlocksUsed = $globalStatus['Key_blocks_used'];
$keyBlocksUnused = $globalStatus['Key_blocks_unused'];
$keyCacheBlockSize = $globalVariables['key_cache_block_size'];
$keyBufferSize = $globalVariables['key_buffer_size'];
$dataDir = $globalVariables['datadir'];
$versionCompileMachine = $globalVariables['version_compile_machine'];

if ($keyReads == 0) {
    $keyCacheMissRate = 0;
    $keyBufferFree = $keyBlocksUnused * $keyCacheBlockSize / $keyBufferSize * 100;
}
else {
    $keyCacheMissRate = $keyReadRequests / $keyReads;
    if (!empty($keyBlocksUnused)) {
        $keyBufferFree = $keyBlocksUnused * $keyCacheBlockSize / $keyBufferSize * 100;
    }
    else {
        $keyBufferFree = "Unknown";
    }
}

/* MyISAM Index Length */
$myisamIndexLength = 0;
$stmt = $pdo->query("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS index_length FROM information_schema.TABLES WHERE ENGINE='MyISAM'");
$myisamIndexLength = $stmt->fetch()['index_length'];

/* Query cache */
$queryCacheType = $globalVariables['query_cache_type'];
$queryCacheSize = $globalVariables['query_cache_size'];
$queryCacheLimit = $globalVariables['query_cache_limit'];
$queryCacheMinResUnit = $globalVariables['query_cache_min_res_unit'];
$qcacheHits = $globalStatus['Qcache_hits'];
$comSelect = $globalStatus['Com_select'];
$qcacheFreeMemory = $globalStatus['Qcache_free_memory'];
$qcacheTotalBlocks = $globalStatus['Qcache_total_blocks'];
$qcacheFreeBlocks = $globalStatus['Qcache_free_blocks'];
$qcacheLowmemPrunes = $globalStatus['Qcache_lowmem_prunes'];

$qcacheUsedMemory = $queryCacheSize - $qcacheFreeMemory;
$qcacheMemFillRatio = $qcacheUsedMemory * 100 / $queryCacheSize;
$queryCacheEfficiency = $qcacheHits / ($comSelect + $qcacheHits);

/* Sort operations */
$sortMergePasses = $globalStatus['Sort_merge_passes'];
$sortScan = $globalStatus['Sort_scan'];
$sortRange = $globalStatus['Sort_range'];
$sortBufferSize = $globalVariables['sort_buffer_size'];
$readRndBufferSize = $globalVariables['read_rnd_buffer_size'];
$totalSorts = $sortScan + $sortRange;
$passesPerSort = ($sortMergePasses > 0) ? $sortMergePasses / $totalSorts : 0;

/* Joins */
$selectFullJoin = $globalStatus['Select_full_join'];
$selectRangeCheck = $globalStatus['Select_range_check'];
$joinsWithoutIndexesPerDay = ($selectFullJoin + $selectRangeCheck) / ($uptime / 86400);
$joinBufferSize = $globalVariables['join_buffer_size'];
$raiseJoinBuffer = ($selectFullJoin > 0 || $selectRangeCheck > 0);

/* Open files limit */
$openFilesLimit = $globalVariables['open_files_limit'];
$openFiles = $globalStatus['Open_files'];
$openFilesRatio = $openFiles * 100 / $openFilesLimit;

/* Table cache */
$dataDir = $globalVariables['datadir'];
$tableCache = $globalVariables['table_cache'];
$tableOpenCache = $globalVariables['table_open_cache'];
$tableOpenCacheInstances = $globalVariables['table_open_cache_instances'];
$tableDefinitionCache = $globalVariables['table_definition_cache'];
$openTables = $globalStatus['Open_tables'];
$openedTables = $globalStatus['Opened_tables'];
$openTableDefinitions = $globalStatus['Open_table_definitions'];

if ($tableOpenCache) $tableCache = $tableOpenCache;

$tableCount = 0;
$stmt = $pdo->query("SELECT COUNT(*) AS table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'");
$tableCount = $stmt->fetch()['table_count'];

if ($openedTables != 0 && $tableCache != 0) {
    $tableCacheHitRate = $openTables * 100 / $openedTables;
    $tableCacheFill = $openTables * 100 / $tableCache;
}
elseif ($openedTables == 0 && $tableCache != 0) {
    $tableCacheHitRate = 100;
    $tableCacheFill = $openTables * 100 / $tableCache;
}
else {
    $tableCacheError = true;
}

/* Temp tables */
$createdTmpTables = $globalStatus['Created_tmp_tables'];
$createdTmpDiskTables = $globalStatus['Created_tmp_disk_tables'];
$tmpTableSize = $globalVariables['tmp_table_size'];
$maxHeapTableSize = $globalVariables['max_heap_table_size'];
$tmpDiskTables = ($createdTmpTables == 0) ? 0 : $createdTmpDiskTables * 100 / ($createdTmpTables + $createdTmpDiskTables);

/* Table scans */
$comSelect = $globalStatus['Com_select'];
$handlerReadRndNext = $globalStatus['Handler_read_rnd_next'];
$readBufferSize = $globalVariables['read_buffer_size'];

if ($comSelect > 0) {
    $fullTableScans = $handlerReadRndNext / $comSelect;
}

/* Table locking */
$tableLocksWaited = $globalStatus['Table_locks_waited'];
$tableLocksImmediate = $globalStatus['Table_locks_immediate'];
$concurrentInsert = $globalVariables['concurrent_insert'];
$lowPriorityUpdates = $globalVariables['low_priority_updates'];

$tableLocksWaitedPct = percentage($tableLocksWaited, $tableLocksImmediate);

$immediateLocksMissRate = ($tableLocksWaited > 0) ? $tableLocksImmediate / $tableLocksWaited : 99999;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <title>MySQL-Tuner-PHP</title>
    <style>
        body {
            background-color: rgb(241, 244, 246);
        }

        .card-header {
            background-color: white;
            text-transform: uppercase;
            color: rgba(13, 27, 62, 0.5);
            font-weight: bold;
        }

        .table-sm th, .table-sm td {
            font-size: .9em;
            padding: 1px;
        }

        .container .row + .row {
            margin-bottom: 1em;
        }
    </style>
</head>
<body>
<div class='container'>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <a class="navbar-brand" href="#">
            <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-calculator text-warning" fill="currentColor"
                 xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                      d="M12 1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4z"/>
                <path d="M4 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-2zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm3-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-4z"/>
            </svg>
            MySQL-Tuner-PHP <small>0.3</small></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarMisc" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        Misc
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarMisc">
                        <a class="dropdown-item" href="#slow_queries">Slow queries</a>
                        <a class="dropdown-item" href="#binary_log">Binary log</a>
                        <a class="dropdown-item" href="#threads">Threads</a>
                        <a class="dropdown-item" href="#used_connections">Used connections</a>
                        <a class="dropdown-item" href="#innodb">InnoDB</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarMemory" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        Memory
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarMemory">
                        <a class="dropdown-item" href="#memory_usage">Memory used</a>
                        <a class="dropdown-item" href="#key_buffer">Key buffer</a>
                        <a class="dropdown-item" href="#query_cache">Query cache</a>
                        <a class="dropdown-item" href="#sort_operations">Sort operations</a>
                        <a class="dropdown-item" href="#join_operations">Join operations</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarFile" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        File
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarFile">
                        <a class="dropdown-item" href="#open_files">Open files</a>
                        <a class="dropdown-item" href="#table_cache">Table cache</a>
                        <a class="dropdown-item" href="#temp_tables">Temp. tables</a>
                        <a class="dropdown-item" href="#table_scans">Table scans</a>
                        <a class="dropdown-item" href="#table_locking">Table locking</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#status_variables">Status vars</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#system_variables">System vars</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class='row'>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Host:</td>
                                    <td><?= $host ?></td>
                                </tr>
                                <tr>
                                    <td>User:</td>
                                    <td><?= $user ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-6'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Server version:</td>
                                    <td><?= $version ?></td>
                                </tr>
                                <tr>
                                    <td>Major version:</td>
                                    <td><?= $majorVersion ?></td>
                                </tr>
                                <tr>
                                    <td>Compile machine:</td>
                                    <td><?= $versionCompileMachine ?></td>
                                </tr>
                                <tr>
                                    <td>Data dir:</td>
                                    <td><samp><?= $dataDir ?></samp></td>
                                </tr>
                                <tr>
                                    <td>Error log:</td>
                                    <td><samp><?= $logError ?></samp></td>
                                </tr>
                            </table>
                        </div>
                        <div class='col-sm-6'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Uptime:</td>
                                    <td><?= $uptime ?> seconds</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td><?= human_readable_time($uptime) ?></td>
                                </tr>
                                <tr>
                                    <td>Questions:</td>
                                    <td><?= $questions ?></td>
                                </tr>
                                <tr>
                                    <td>Avg. qps:</td>
                                    <td><?= round($avgQps, 1) ?></td>
                                </tr>
                                <tr>
                                    <td>Threads connected:</td>
                                    <td><?= $threadsConnected ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <?php
        if ($uptime < 172800) {
            ?>
            <div class="alert alert-danger" role="alert">
                Server has not been running for at least 48hrs. It may not be safe to use these recommendations!
            </div>
            <?php
        }
        ?>
    </div>
    <div class='row'>
        <a id='engines'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Database engines</div>
                <div class='card-body'>
                    <table class='table table-sm'>
                        <tr>
                            <th>Engine</th>
                            <th>Support</th>
                            <th>Comment</th>
                            <th>Transactions</th>
                            <th>XA</th>
                            <th>Savepoints</th>
                        </tr>
                        <?php
                        foreach ($engines as $engineName => $engineConfig) {
                            ?>
                            <tr>
                                <td><?= $engineName ?></td>
                                <td><?= $engineConfig['Support'] ?></td>
                                <td><?= $engineConfig['Comment'] ?></td>
                                <td><?= $engineConfig['Transactions'] ?></td>
                                <td><?= $engineConfig['XA'] ?></td>
                                <td><?= $engineConfig['Savepoints'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='slow_queries'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Slow queries</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>The slow query log is a record of SQL queries that took a long time to perform. Note
                                that, if
                                your queries contain user's passwords, the slow query log may contain passwords too.
                                Thus, it
                                should be protected.</p>
                            <p>More information on the Slow Query Log:<br>
                                <a href='https://mariadb.com/kb/en/slow-query-log-overview/' target='_blank'>https://mariadb.com/kb/en/slow-query-log-overview/</a>
                            </p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Slow query log:</td>
                                    <td><?= $slowQueryLog ?>
                                        <?php
                                        if ($slowQueryLog == "ON") {
                                            echo alert_check();
                                        }
                                        elseif ($slowQueryLog == "OFF" or empty($slowQueryLog)) {
                                            echo alert_warning();
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Slow query count:</td>
                                    <td><?= $slowQueries ?> of <?= $questions ?><br><?= $slowQueriesPct ?> %</td>
                                </tr>
                                <tr>
                                    <td>Long query time:</td>
                                    <td><?= round($longQueryTime) ?>
                                        sec.<?= ($longQueryTime > $preferredQueryTime) ? alert_info() : '' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>slow_query_log</samp></td>
                            <td>0</td>
                            <td><?= $slowQueryLog ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_output</samp></td>
                            <td>FILE</td>
                            <td><?= $logOutput ?></td>
                        </tr>
                        <tr>
                            <td><samp>slow_query_log_file</samp></td>
                            <td><i>host_name</i>-slow.log</td>
                            <td><?= $slowQueryLogFile ?></td>
                        </tr>
                        <tr>
                            <td><samp>long_query_time</samp></td>
                            <td>10.000000</td>
                            <td><?= $longQueryTime ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_queries_not_using_indexes</samp></td>
                            <td>OFF</td>
                            <td><?= $logQueriesNotUsingIndexes ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_slow_admin_statements</samp></td>
                            <td>ON <span class='text-muted'>(>= MariaDB 10.2.4)</span><br>OFF <span class='text-muted'>(<= MariaDB 10.2.3)</span>
                            </td>
                            <td><?= $logSlowAdminStatements ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_slow_disabled_statements</samp></td>
                            <td>sp</td>
                            <td><?= $logSlowDisabledStatements ?></td>
                        </tr>
                        <tr>
                            <td><samp>min_examined_row_limit</samp></td>
                            <td>0</td>
                            <td><?= $minExaminedRowLimit ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_slow_rate_limit</samp></td>
                            <td>1</td>
                            <td><?= $logSlowRateLimit ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_slow_verbosity</samp></td>
                            <td><i>(empty)</i></td>
                            <td><?= $logSlowVerbosity ?></td>
                        </tr>
                        <tr>
                            <td><samp>log_slow_filter</samp></td>
                            <td><?= human_readable_comma_enum("admin, filesort, filesort_on_disk, full_join, full_scan, query_cache, query_cache_miss, tmp_table, tmp_table_on_disk") ?></td>
                            <td><?= human_readable_comma_enum($logSlowFilter) ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($slowQueryLog == "OFF" or empty($slowQueryLog)) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Your Slow Query Log is NOT enabled. Enable the Slow Query Log to examine slow queries which
                            execution time exceeds the value of <samp>long_query_time</samp>.
                        </div>
                        <?php
                    }
                    if ($longQueryTime > $preferredQueryTime) {
                        ?>
                        <div class="alert alert-info" role="alert">
                            Configure <samp>long_query_time</samp> to a lower value, to investigate your slow queries
                            even better. Recommendation: <?= $preferredQueryTime ?> sec.
                        </div>
                        <?php
                    }
                    elseif (round($longQueryTime) == 0) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Configure <samp>long_query_time</samp> to a higher value. The current setting of zero, will
                            cause ALL queries to be logged! If you actually want to log all queries, use the query log,
                            not the slow query log.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='binary_log'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Binary log</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>The binary log contains a record of all changes to the databases, both data and
                                structure, as well as how long each statement took to execute. It consists of a set of
                                binary log files and an index.</p>
                            <p>More information on the Binary Log:<br>
                                <a href='https://mariadb.com/kb/en/overview-of-the-binary-log/' target='_blank'>https://mariadb.com/kb/en/overview-of-the-binary-log/</a>
                            </p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Log bin:</td>
                                    <td><?= $logBin;
                                        echo ($logBin == "ON") ? alert_check() : alert_error(); ?></td>
                                </tr>
                                <tr>
                                    <td>Max binlog size:</td>
                                    <td><?= human_readable_bytes($maxBinlogSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Expire logs days:</td>
                                    <td><?= $expireLogsDays ?></td>
                                </tr>
                                <tr>
                                    <td>Sync binlog:</td>
                                    <td><?= $syncBinlog ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>log_bin</samp></td>
                            <td>OFF</td>
                            <td><?= $logBin ?></td>
                        </tr>
                        <tr>
                            <td><samp>max_binlog_size</samp></td>
                            <td>1073741824 <span class='text-muted'>(1 GB)</span></td>
                            <td><?= $maxBinlogSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($maxBinlogSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>expire_logs_days</samp></td>
                            <td>0</td>
                            <td><?= $expireLogsDays ?></td>
                        </tr>
                        <tr>
                            <td><samp>sync_binlog</samp></td>
                            <td>0</td>
                            <td><?= $syncBinlog ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($logBin != "ON") {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            The binary log is not enabled.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='threads'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Threads</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>Connection manager threads handle client connection requests on the network interfaces
                                that the server listens to. On all platforms, one manager thread handles TCP/IP
                                connection requests. On Unix, this manager thread also handles Unix socket file
                                connection requests. On Windows, a manager thread handles shared-memory connection
                                requests, and another handles named-pipe connection requests. The server does not create
                                threads to handle interfaces that it does not listen to. For example, a Windows server
                                that does not have support for named-pipe connections enabled does not create a thread
                                to handle them.</p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Thread handling:</td>
                                    <td><?= $threadHandling ?></td>
                                </tr>
                                <tr>
                                    <td>Thread cache size:</td>
                                    <td><?= $threadCacheSize ?></td>
                                </tr>
                                <tr>
                                    <td>Threads cached:</td>
                                    <td><?= $threadsCached ?></td>
                                </tr>
                                <tr>
                                    <td>Threads per sec. avg.:</td>
                                    <td><?= round($historicThreadsPerSec, 2) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>thread_handling</samp></td>
                            <td>one-thread-per-connection</td>
                            <td><?= $threadHandling ?></td>
                        </tr>
                        <tr>
                            <td><samp>thread_cache_size</samp></td>
                            <td>0 <span class='text-muted'>(<= MariaDB 10.1)</span><br>256 <span class='text-muted'>(from MariaDB 10.2.0)</span>
                            </td>
                            <td><?= $threadCacheSize ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($historicThreadsPerSec > 2 && $threadsCached < 1) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            Threads created per/sec are overrunning threads cached
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your thread_cache_size is fine
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='used_connections'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Used connections</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Max connections:</td>
                                    <td><?= $maxConnections ?></td>
                                </tr>
                                <tr>
                                    <td>Threads connected:</td>
                                    <td><?= $threadsConnected ?></td>
                                </tr>
                                <tr>
                                    <td>Max used connections:</td>
                                    <td><?= $maxUsedConnections ?><br><?= round($maxConnectionsUsage, 1) ?> %</td>
                                </tr>
                                <tr>
                                    <td>Aborted connects:</td>
                                    <td><?= $abortedConnects ?><br><?= round($abortedConnectsPct, 1) ?> %</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>max_connections</samp></td>
                            <td>151</td>
                            <td><?= $maxConnections ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($maxConnectionsUsage > 85) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            You should raise max_connections
                        </div>
                        <?php
                    }
                    elseif ($maxConnectionsUsage < 10) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            You are using less than 10% of your configured max_connections. Lowering max_connections
                            could
                            help to avoid an over-allocation of memory.
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your max_connections variable seems to be fine
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='innodb'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>InnoDB</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>The XtraDB/InnoDB buffer pool is a key component for optimizing MariaDB. It stores data
                                and indexes, and you usually want it as large as possible so as to keep as much of the
                                data and indexes in memory, reducing disk IO, as main bottleneck.</p>
                            <p>The buffer pool attempts to keep frequently-used blocks in the buffer, and so essentially
                                works as two sublists, a new sublist of recently-used information, and an old sublist of
                                older information. By default, 37% of the list is reserved for the old list.</p>
                            <p>When new information is accessed that doesn't appear in the list, it is placed at the top
                                of the old list, the oldest item in the old list is removed, and everything else bumps
                                back one position in the list.</p>
                            <p>When information is accessed that appears in the old list, it is moved to the top the new
                                list, and everything above moves back one position.</p>
                            <p>More information on the InnoDB Buffer Pool:<br>
                                <a href='https://mariadb.com/kb/en/innodb-buffer-pool/' target='_blank'>https://mariadb.com/kb/en/innodb-buffer-pool/</a>
                            </p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Index space:</td>
                                    <td><?= human_readable_bytes($innodbIndexLength) ?></td>
                                </tr>
                                <tr>
                                    <td>Data space:</td>
                                    <td><?= human_readable_bytes($innodbDataLength) ?></td>
                                </tr>
                                <tr>
                                    <td>Data read / written:</td>
                                    <td><?= human_readable_bytes($innodbDataRead) ?>
                                        / <?= human_readable_bytes($innodbDataWritten) ?></td>
                                </tr>
                                <tr>
                                    <td>Data reads / writes:</td>
                                    <td><?= $innodbDataReads ?>
                                        / <?= $innodbDataWrites ?></td>
                                </tr>
                                <tr>
                                    <td>Buffer pool size:</td>
                                    <td><?= human_readable_bytes($innodbBufferPoolSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Buffer pool free pct.:</td>
                                    <td><?= round($innodbBufferPoolFreePct, 1) ?> %</td>
                                </tr>
                                <tr>
                                    <td>Buffer pool bytes data:</td>
                                    <td><?= human_readable_bytes($innodbBufferPoolBytesData) ?></td>
                                </tr>
                                <tr>
                                    <td>Buffer pool bytes dirty:</td>
                                    <td><?= human_readable_bytes($innodbBufferPoolBytesDirty) ?></td>
                                </tr>
                                <tr>
                                    <td>Buffer pool read requests:</td>
                                    <td><?= $innodbBufferPoolReadRequests ?></td>
                                </tr>
                                <tr>
                                    <td>Buffer pool reads:</td>
                                    <td><?= $innodbBufferPoolReads ?></td>
                                </tr>
                                <tr>
                                    <td>Buffer pool read ratio:</td>
                                    <td><?= round($innodbBufferPoolReadRatio, 2) ?> %</td>
                                </tr>
                                <tr>
                                    <td>Buffer pool wait free:</td>
                                    <td><?= $innodbBufferPoolWaitFree ?></td>
                                </tr>
                                <tr>
                                    <td>Row lock time:</td>
                                    <td><?= $innodbRowLockTime ?> msec.</td>
                                </tr>
                                <tr>
                                    <td>Row lock waits:</td>
                                    <td><?= $innodbRowLockWaits ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>innodb_buffer_pool_size</samp></td>
                            <td>134217728 (128 MB)</td>
                            <td><?= $innodbBufferPoolSize ?> (<?= human_readable_bytes($innodbBufferPoolSize) ?>)</td>
                        </tr>
                        <tr>
                            <td><samp>innodb_fast_shutdown</samp></td>
                            <td>1</td>
                            <td><?= $innodbFastShutdown ?></td>
                        </tr>
                        <tr>
                            <td><samp>innodb_file_per_table</samp></td>
                            <td>ON</td>
                            <td><?= $innodbFilePerTable ?></td>
                        </tr>
                        <tr>
                            <td><samp>innodb_flush_log_at_trx_commit</samp></td>
                            <td>1</td>
                            <td><?= $innodbFlushLogAtTrxCommit ?></td>
                        </tr>
                        <tr>
                            <td><samp>innodb_log_buffer_size</samp></td>
                            <td>16777216 (16MB) >= MariaDB 10.1.9<br>8388608 (8MB) <= MariaDB 10.1.8</td>
                            <td><?= $innodbLogBufferSize ?> (<?= human_readable_bytes($innodbLogBufferSize) ?>)</td>
                        </tr>
                        <tr>
                            <td><samp>innodb_log_file_size</samp></td>
                            <td>100663296 (96MB) (>= MariaDB 10.5)<br>50331648 (48MB) (<= MariaDB 10.4)</td>
                            <td><?= $innodbLogFileSize ?> (<?= human_readable_bytes($innodbLogFileSize) ?>)</td>
                        </tr>
                        <tr>
                            <td><samp>innodb_log_files_in_group</samp></td>
                            <td>1 <span class='text-muted'>(>= MariaDB 10.5)</span><br>2 (<= MariaDB 10.4)</td>
                            <td><?= $innodbLogFilesInGroup ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='memory_usage'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Memory usage</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Physical Memory:</td>
                                    <td><?= human_readable_bytes($physicalMemory); ?></td>
                                </tr>
                                <tr>
                                    <td>Global buffer size:</td>
                                    <td><?= human_readable_bytes($globalBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Per-thread buffer size:</td>
                                    <td><?= human_readable_bytes($perThreadBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Max theoretical memory:</td>
                                    <td><?= human_readable_bytes($totalMemory) ?></td>
                                </tr>
                                <tr>
                                    <td>Max used memory:</td>
                                    <td><?= human_readable_bytes($maxMemory) ?></td>
                                </tr>
                                <tr>
                                    <td>Pct of sys mem:</td>
                                    <td><?= round($pctOfSysMem, 1) ?> %</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-4'>
                            <h5>Global buffer size</h5>
                            <p>Calculation of the global available buffers in this server.</p>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Tmp table size:</td>
                                    <td align='right'><?= $tmpTableSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>InnoDB buffer pool size:</td>
                                    <td align='right'><?= $innodbBufferPoolSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>InnoDB additional mem pool size:</td>
                                    <td align='right'><?= $innodbAdditionalMemPoolSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>InnoDB log buffer size:</td>
                                    <td align='right'><?= $innodbLogBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Key buffer size:</td>
                                    <td align='right'><?= $keyBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Query cache size:</td>
                                    <td align='right'><?= $queryCacheSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Aria pagecache buffer size:</td>
                                    <td align='right'><?= $ariaPagecacheBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td><strong>Total global buffer size:</strong></td>
                                    <td align='right'><strong><?= $globalBufferSize ?></strong></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align='right'><span
                                                class='text-muted'><?= human_readable_bytes($globalBufferSize) ?></span>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                            </table>
                        </div>
                        <div class='col-sm-4'>
                            <h5>Per-thread buffer size</h5>
                            <p>Calculation of the buffer each independent thread can use.</p>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Read buffer size:</td>
                                    <td align='right'><?= $readBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Read rnd buffer size:</td>
                                    <td align='right'><?= $readRndBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Sort buffer size:</td>
                                    <td align='right'><?= $sortBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Thread stack:</td>
                                    <td align='right'><?= $threadStack ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Net buffer length:</td>
                                    <td align='right'><?= $netBufferLength ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Join buffer size:</td>
                                    <td align='right'><?= $joinBufferSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td>Binlog cache size:</td>
                                    <td align='right'><?= $binlogCacheSize ?></td>
                                    <td>+</td>
                                </tr>
                                <tr>
                                    <td><strong>Total per-thread buffer size:</strong></td>
                                    <td align='right'><strong><?= $perThreadBufferSize ?></strong></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td align='right'><span
                                                class='text-muted'><?= human_readable_bytes($perThreadBufferSize) ?></span>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                            </table>
                        </div>
                        <div class='col-sm-4'>
                            <h5>Total thread buffers</h5>
                            <p>Calculations of the cumulative thread buffers. These are calculation for two different
                                scenarios. Formula: connections &times; buffer size</p>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Max connections:</td>
                                    <td align='right'><?= $maxConnections ?>
                                        &times; <?= human_readable_bytes($perThreadBufferSize) ?> =
                                    </td>
                                    <td align='right'><?= human_readable_bytes($perThreadBuffers) ?></td>
                                </tr>
                                <tr>
                                    <td>Max used connections:</td>
                                    <td align='right'><?= $maxUsedConnections ?>
                                        &times; <?= human_readable_bytes($perThreadBufferSize) ?> =
                                    </td>
                                    <td align='right'><?= human_readable_bytes($perThreadMaxBuffers) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>aria_pagecache_buffer_size</samp></td>
                            <td>134217720 <span class='text-muted'>(128 MB)</span></td>
                            <td><?= $ariaPagecacheBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($ariaPagecacheBufferSize) ?>)</span>
                            </td>
                        </tr>
                        <tr>
                            <td><samp>join_buffer_size</samp></td>
                            <td>262144 <span class='text-muted'>(256 KB)</span></td>
                            <td><?= $joinBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($joinBufferSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>net_buffer_length</samp></td>
                            <td>16384 <span class='text-muted'>(16 KB)</span></td>
                            <td><?= $netBufferLength ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($netBufferLength) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>read_buffer_size</samp></td>
                            <td>131072 <span class='text-muted'>(128 KB)</span></td>
                            <td><?= $readBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($readBufferSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>read_rnd_buffer_size</samp></td>
                            <td>262144 <span class='text-muted'>(256 KB)</span></td>
                            <td><?= $readRndBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($readRndBufferSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>sort_buffer_size</samp></td>
                            <td><span class='text-muted'>(2 MB)</span></td>
                            <td><?= $sortBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($sortBufferSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>thread_stack</samp></td>
                            <td>299008 <span class='text-muted'>(<?= human_readable_bytes(299008) ?>)</span></td>
                            <td><?= $threadStack ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($threadStack) ?>)</span></td>
                        </tr>
                    </table>
                    <?php
                    if ($pctOfSysMem > 90) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            Max memory limit exceeds 90% of physical memory
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='key_buffer'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Key buffer</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>key_buffer_size is a MyISAM variable which determines the size of the index buffers held
                                in memory, which affects the speed of index reads. Note that Aria tables by default make
                                use of an alternative setting, aria-pagecache-buffer-size.</p>
                            <p>More information on optimizing the Key Buffer Size:<br>
                                <a href='https://mariadb.com/kb/en/optimizing-key_buffer_size/' target='_blank'>https://mariadb.com/kb/en/optimizing-key_buffer_size/</a>
                            </p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>MyISAM Index Size:</td>
                                    <td><?= human_readable_bytes($myisamIndexLength) ?></td>
                                </tr>
                                <tr>
                                    <td>Key buffer size:</td>
                                    <td><?= human_readable_bytes($keyBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Key cache miss rate is:</td>
                                    <td>1 : <?= round($keyCacheMissRate) ?></td>
                                </tr>
                                <tr>
                                    <td>Key buffer usage:</td>
                                    <td><?= round($keyBufferFree) ?> %</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>key_buffer_size</samp></td>
                            <td>134217728 <span class='text-muted'>(128 MB)</span></td>
                            <td><?= $keyBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($keyBufferSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>key_cache_block_size</samp></td>
                            <td>1024 <span class='text-muted'>(1 KB)</span></td>
                            <td><?= $keyCacheBlockSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($keyCacheBlockSize) ?>)</span></td>
                        </tr>
                    </table>
                    <?php
                    if ($keyReads == 0) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            No key reads?! Seriously look into using some indexes
                        </div>
                        <?php
                    }
                    if ($keyCacheMissRate <= 100 && $keyCacheMissRate > 0 && $keyBufferFree < 20) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            You could increase key_buffer_size. It is safe to raise this up to 1/4 of total system
                            memory.
                        </div>
                        <?php
                    }
                    elseif ($keyCacheMissRate >= 10000 || $keyBufferFree < 50) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Your key_buffer_size seems to be too high. Perhaps you can use these resources elsewhere.
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your key_buffer_size seems to be fine
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='query_cache'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Query cache</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>The query cache stores results of SELECT queries so that if the identical query is
                                received in future, the results can be quickly returned.</p>
                            <p>This is extremely useful in high-read, low-write environments (such as most websites). It
                                does not scale well in environments with high throughput on multi-core machines, so it
                                is disabled by default.</p>
                            <p>More information on optimizing the Query Cache:<br>
                                <a href='https://mariadb.com/kb/en/query-cache/' target='_blank'>https://mariadb.com/kb/en/query-cache/</a>
                            </p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Query cache type:</td>
                                    <td><?= $queryCacheType ?></td>
                                </tr>
                                <tr>
                                    <td>Query cache size:</td>
                                    <td><?= human_readable_bytes($queryCacheSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Query cache used memory:</td>
                                    <td><?= human_readable_bytes($qcacheUsedMemory) ?></td>
                                </tr>
                                <tr>
                                    <td>Query cache usage:</td>
                                    <td><?= round($qcacheMemFillRatio, 1) ?> %</td>
                                </tr>
                                <tr>
                                    <td>Query cache efficiency:</td>
                                    <td><?= $queryCacheEfficiency ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>query_cache_limit</samp></td>
                            <td>1048576 <span class='text-muted'>(1 MB)</span></td>
                            <td><?= $queryCacheLimit ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($queryCacheLimit) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>query_cache_min_res_limit</samp></td>
                            <td>4096 <span class='text-muted'>(4 KB)</span></td>
                            <td><?= $queryCacheMinResUnit ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($queryCacheMinResUnit) ?>)</span>
                            </td>
                        </tr>
                        <tr>
                            <td><samp>query_cache_size</samp></td>
                            <td>1 M<span class='text-muted'>(>= MariaDB 10.1.7)</span><br>0 <span class='text-muted'>(<= MariaDB 10.1.6)</span>
                            </td>
                            <td><?= $queryCacheSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($queryCacheSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>query_cache_type</samp></td>
                            <td>OFF <span class='text-muted'>(>= MariaDB 10.1.7)</span><br>ON <span class='text-muted'>(<= MariaDB 10.1.6)</span>
                            </td>
                            <td><?= $queryCacheType ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($queryCacheSize == 0) {
                        ?>
                        <div class="alert alert-info" role="alert">
                            Query cache is supported but not enabled. Perhaps you should set the query_cache_size
                        </div>
                        <?php
                    }
                    if ($queryCacheSize > 0 && $queryCacheType) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Query cache is disabled by query_cache_type, but effectively enabled because
                            query_cache_size is higher than zero.
                        </div>
                        <?php
                    }
                    if ($qcacheMemFillRatio < 25) {
                        ?>
                        <div class="alert alert-info" role="alert">
                            Your query cache size seems to be too high. Perhaps you can use these resources elsewhere.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='sort_operations'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Sort operations</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p>Each session performing a sort allocates a buffer with this amount of memory. Not
                                specific to any storage engine. If the status variable sort_merge_passes is too high,
                                you may need to look at improving your query indexes, or increasing this. Consider
                                reducing where there are many small sorts, such as OLTP, and increasing where needed by
                                session. 16k is a suggested minimum.</p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Sort buffer size:</td>
                                    <td><?= human_readable_bytes($sortBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Read rnd buffer size:</td>
                                    <td><?= human_readable_bytes($readRndBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Sort merge passes:</td>
                                    <td><?= $sortMergePasses ?></td>
                                </tr>
                                <tr>
                                    <td>Passes per sort:</td>
                                    <td><?= $passesPerSort ?></td>
                                </tr>
                                <tr>
                                    <td>Total sorts:</td>
                                    <td><?= $totalSorts ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>sort_buffer_size</samp></td>
                            <td>2 M</span></td>
                            <td><?= human_readable_bytes($sortBufferSize) ?></td>
                        </tr>
                        <tr>
                            <td><samp>read_rnd_buffer_size</samp></td>
                            <td>262144 <span class='text-muted'>(256 KB)</span></td>
                            <td><?= $readRndBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($readRndBufferSize) ?>)</span></td>
                        </tr>
                    </table>
                    <?php
                    if ($totalSorts == 0) {
                        ?>
                        <div class="alert alert-info" role="alert">
                            No sort operations have been performed
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='join_operations'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Joins</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Join buffer size:</td>
                                    <td><?= human_readable_bytes($joinBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Select full join:</td>
                                    <td><?= $selectFullJoin ?></td>
                                </tr>
                                <tr>
                                    <td>Select range check:</td>
                                    <td><?= $selectRangeCheck ?></td>
                                </tr>
                                <tr>
                                    <td>Joins without indexes per day:</td>
                                    <td><?= round($joinsWithoutIndexesPerDay) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>join_buffer_size</samp></td>
                            <td>262144 <span class='text-muted'>(256 KB)</span></td>
                            <td><?= human_readable_bytes($joinBufferSize) ?>
                                <span class='text-muted'>(<?= human_readable_bytes($joinBufferSize) ?>)</span>
                            </td>
                        </tr>
                    </table>
                    <?php
                    if ($selectRangeCheck == 0 && $selectFullJoin == 0) {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your joins seem to be using indexes properly
                        </div>
                        <?php
                    }
                    if ($selectFullJoin > 0 || $selectRangeCheck > 0) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            You should enable "log-queries-not-using-indexes" and look for non indexed joins in the slow
                            query log.
                        </div>
                        <?php
                        if ($raiseJoinBuffer) {
                            ?>
                            <div class="alert alert-info" role="alert">
                                If you are unable to optimize your queries you may want to increase your
                                join_buffer_size to
                                accomodate larger joins in one pass.
                            </div>
                            <?php
                        }
                    }
                    if ($joinBufferSize >= 4 * 1024 * 1024) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            It is not advised to have more than 4 M join_buffer_size.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='open_files'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Open files limit</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            <p> The number of file descriptors available to MariaDB. If you are getting the Too many
                                open files error, then you should increase this limit.</p>
                            <p>If set to 0, then MariaDB will calculate a limit based on the following:</p>
                            <p>MAX(max_connections * 5, max_connections + table_open_cache * 2)</p>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Open files limit:</td>
                                    <td><?= $openFilesLimit ?></td>
                                </tr>
                                <tr>
                                    <td>Open files:</td>
                                    <td><?= $openFiles ?></td>
                                </tr>
                                <tr>
                                    <td>Open files ratio:</td>
                                    <td><?= round($openFilesRatio, 1) ?> %</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>open_files_limit</samp></td>
                            <td><em>Autosized</em></td>
                            <td><?= $openFilesLimit ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($openFilesRatio >= 75) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            You currently have open more than 75% of your open_file_limit.
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your open_files_limit value seems to be fine.
                        </div>
                        <?php

                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='table_cache'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Table cache</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Table count:</td>
                                    <td><?= $tableCount ?></td>
                                </tr>
                                <tr>
                                    <td>Table cache:</td>
                                    <td><?= $openTables ?> of <?= $tableOpenCache ?><br>
                                        <?= round($tableCacheFill, 1) ?> %
                                    </td>
                                </tr>
                                <tr>
                                    <td>Definition cache:</td>
                                    <td><?= $openTableDefinitions ?> of <?= $tableDefinitionCache ?></td>
                                </tr>
                                <tr>
                                    <td>Table cache hit rate:</td>
                                    <td><?= round($tableCacheHitRate, 2) ?> %</td>
                                </tr>
                                <tr>
                                    <td>Opened tables:</td>
                                    <td><?= $openedTables ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>table_open_cache</samp></td>
                            <td>2000</td>
                            <td><?= $tableOpenCache ?></td>
                        </tr>
                        <tr>
                            <td><samp>table_open_cache_instances</samp></td>
                            <td>8</td>
                            <td><?= $tableOpenCacheInstances ?></td>
                        </tr>
                        <tr>
                            <td><samp>table_definition_cache</samp></td>
                            <td>400</td>
                            <td><?= $tableDefinitionCache ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($tableCacheError) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            No table cache?!
                        </div>
                        <?php
                    }

                    if ($tableCacheFill < 95) {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your table_cache value seems to be fine
                        </div>
                        <?php
                    }
                    elseif ($tableCacheHitRate <= 85 || $tableCacheFill >= 95) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            You should probably increase your table_cache
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your table_cache value seems to be fine.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='temp_tables'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Temp tables</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                            Note! BLOB and TEXT colums are now allowed in memory tables. If you are using there columns
                            raising these values might not impact your ratio of on disk temp tables.
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Max heap table size:</td>
                                    <td><?= human_readable_bytes($maxHeapTableSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Tmp table size:</td>
                                    <td><?= human_readable_bytes($tmpTableSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Created tmp tables:</td>
                                    <td><?= $createdTmpTables ?></td>
                                </tr>
                                <tr>
                                    <td>Created tmp disk tables:</td>
                                    <td><?= $createdTmpDiskTables ?><br><?= round($tmpDiskTables, 1) ?> %</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>max_heap_table_size</samp></td>
                            <td>16777216 <span class='text-muted'>(16MB)</span></td>
                            <td><?= $maxHeapTableSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($maxHeapTableSize) ?>)</span></td>
                        </tr>
                        <tr>
                            <td><samp>tmp_table_size</samp></td>
                            <td>16777216 <span class='text-muted'>(16MB)</span></td>
                            <td><?= $tmpTableSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($tmpTableSize) ?>)</span></td>
                        </tr>
                    </table>
                    <?php
                    if ($tmpTableSize > $maxHeapTableSize) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Effective in-memory tmp_table_size is limited to max_heap_table_size.
                        </div>
                        <?php
                    }

                    if ($tmpDiskTables >= 25) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            Perhaps you should increase your tmp_table_size and/or max_heap_table_size to reduce the
                            number
                            of disk-based temperary tables.<br>
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your temporary tables ratio to be fine.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='table_scans'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Table scans</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Read buffer size:</td>
                                    <td><?= human_readable_bytes($readBufferSize) ?></td>
                                </tr>
                                <tr>
                                    <td>Full table scans ratio:</td>
                                    <td><?= round($fullTableScans) ?> : 1</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>read_buffer_size</samp></td>
                            <td>131072 <span class='text-muted'>(128 KB)</span></td>
                            <td><?= $readBufferSize ?> <span
                                        class='text-muted'>(<?= human_readable_bytes($readBufferSize) ?>)</span></td>
                        </tr>
                    </table>
                    <?php
                    if ($comSelect > 0) {
                        if ($fullTableScans >= 4000 && $readBufferSize < 2 * 1024 * 1024) {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                You have a high ratio of sequential access requests to SELECTs. You may benefit from
                                raising
                                read_buffer_size and/or improving your use of indexes.
                            </div>
                            <?php
                        }
                        elseif ($readBufferSize > 8 * 1024 * 1024) {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                Read buffer is over 8 MB. There is probably no need for such a large read_buffer.
                            </div>
                            <?php
                        }
                        else {
                            ?>
                            <div class="alert alert-success" role="alert">
                                Read buffer size seems to be fine.
                            </div>
                            <?php
                        }
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Read buffer size seems to be fine.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='table_locking'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Table locking</div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-sm-8'>
                        </div>
                        <div class='col-sm-4'>
                            <table class='table table-sm'>
                                <tr>
                                    <td>Table locks immediate:</td>
                                    <td><?= $tableLocksImmediate ?></td>
                                </tr>
                                <tr>
                                    <td>Table locks waited:</td>
                                    <td><?= $tableLocksWaited ?><br>
                                        <?= round($tableLocksWaitedPct,3) ?> %
                                    </td>
                                </tr>
                                <tr>
                                    <td>Lock / wait ratio:</td>
                                    <td>1 : <?= round($immediateLocksMissRate) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <table class='table table-sm'>
                        <tr>
                            <th style='width: 30%'>Variable name</th>
                            <th style='width: 35%'>Default value</th>
                            <th style='width: 35%'>Current value</th>
                        </tr>
                        <tr>
                            <td><samp>concurrent_insert</samp></td>
                            <td>AUTO</td>
                            <td><?= $concurrentInsert ?></td>
                        </tr>
                        <tr>
                            <td><samp>low_priority_updates</samp></td>
                            <td>0</td>
                            <td><?= $lowPriorityUpdates ?></td>
                        </tr>
                    </table>
                    <?php
                    if ($immediateLocksMissRate < 5000) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            You may benefit from selective use of InnoDB
                        </div>
                        <?php
                    }
                    else {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Your table locking seems to be fine
                        </div>
                        <?php

                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='status_variables'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>Status variables</div>
                <div class='card-body'>
                    <a class="btn btn-primary" data-toggle="collapse" href="#collapseStatus" role="button"
                       aria-expanded="false"
                       aria-controls="collapseStatus">Show status</a>
                    <table class='table table-sm collapse' id='collapseStatus'>
                        <?php
                        foreach ($globalStatus as $globalStatusName => $globalStatusValue) {
                            echo "<tr><td>" . $globalStatusName . "</td><td>" . $globalStatusValue . "</td></tr>\n";
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <a id='system_variables'></a>
        <div class='col-sm-12'>
            <div class='card border-0 shadow-sm'>
                <div class='card-header'>System variables</div>
                <div class='card-body'>
                    <a class="btn btn-primary" data-toggle="collapse" href="#collapseVariables" role="button"
                       aria-expanded="false"
                       aria-controls="collapseVariables">Show variables</a>
                    <table class='table table-sm collapse' id='collapseVariables'>
                        <?php
                        foreach ($globalVariables as $globalVariableName => $globalVariableValue) {
                            echo "<tr><td>" . $globalVariableName . "</td><td>" . $globalVariableValue . "</td></tr>\n";
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class='text-light bg-dark'>
    <div class='container'>
        <div class='row'>
            <div class='col-sm-12'>
                <p style='color: rgb(192, 190, 195)'>MySQL-Tuner-PHP is a open source project of Acropia. Feel free to
                    use whereever and however you want!</p>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-2'>Col</div>
            <div class='col-sm-2'>Col</div>
            <div class='col-sm-2'>Col</div>
            <div class='col-sm-2'>Col</div>
            <div class='col-sm-2'>Col</div>
            <div class='col-sm-2'>Col</div>
        </div>
    </div>
</div>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"
        integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy"
        crossorigin="anonymous"></script>
</body>
</html>
