<?php
$physicalMemory = 4 * 1024 * 1024 * 1024;
$preferredQueryTime = 5;

function human_readable($number) {
    if ($number >= 1024 * 1024 * 1024) {
        return round($number / (1024 * 1024 * 1024), 2) . " G";
    }
    elseif ($number >= 1024 * 1024) {
        return round($number / (1024 * 1024), 2) . " M";
    }
    elseif ($number >= 1024) {
        return round($number / (1024), 2) . " K";
    }
    else {
        return $number . " bytes";
    }
}

function human_readable_time($seconds) {
    return floor($seconds / 86400) . " days, " . ($seconds / 3600 % 24) . " hrs, " . ($seconds / 60 % 60) . " min";
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


$questions = $globalStatus['Questions'];
$uptime = $globalStatus['Uptime'];
$avgQps = $questions / $uptime;
$threadsConnected = $globalStatus['Threads_connected'];

/* Slow queries */
$slowQueries = $globalStatus['Slow_queries'];
$longQueryTime = $globalVariables['long_query_time'];
$slowQueryLog = $globalVariables['slow_query_log'];
$slowQueryLogFile = $globalVariables['slow_query_log_file'];

/* Binary log */
$logBin = $globalVariables['log_bin'];
$maxBinlogSize = $globalVariables['max_binlog_size'];
$expireLogsDays = $globalVariables['expire_logs_days'];
$syncBinlog = $globalVariables['sync_binlog'];

/* Threads */
$threadsCreated = $globalStatus['Threads_created'];
$threadsCached = $globalStatus['Threads_cached'];
$threadsCacheSize = $globalVariables['thread_cache_size'];
$historicThreadsPerSec = $threadsCreated / $uptime;

/* Used connections */
$maxConnections = $globalVariables['max_connections'];
$maxUsedConnections = $globalStatus['Max_used_connections'];
$threadsConnected = $globalStatus['Threads_connected'];
$connectionsRatio = ($maxUsedConnections * 100 / $maxConnections);

/* InnoDB */
$innodbBufferPoolSize = $globalVariables['innodb_buffer_pool_size'];
$innodbAdditionalMemPoolSize = $globalVariables['innodb_additional_mem_pool_size'];
$innodbFastShutdown = $globalVariables['innodb_fast_shutdown'];
$innodbFlushLogAtTrxCommit = $globalVariables['innodb_flush_log_at_trx_commit'];
$innodbLocksUnsafeForBinlog = $globalVariables['innodb_locks_unsafe_for_binlog'];
$innodbLogBufferSize = $globalVariables['innodb_log_buffer_size'];
$innodbLogFileSize = $globalVariables['innodb_log_file_size'];
$innodbLogFilesInGroup = $globalVariables['innodb_log_files_in_group'];
$innodbSafeBinlog = $globalVariables['innodb_safe_binlog'];
$innodbThreadConcurrency = $globalVariables['innodb_thread_concurrency'];

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
    $innodbBufferPoolReadRequests = $globalStatus['Innodb_buffer_pool_read_requests'];
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
$joinBufferSize = $globalVariables['join_buffer_size'];
$tmpTableSize = $globalVariables['tmp_table_size'];
$maxHeapTableSize = $globalVariables['max_heap_table_size'];
$logBin = $globalVariables['log_bin'];
$maxUsedConnections = $globalStatus['Max_used_connections'];

if ($logBin = "ON") {
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

$perThreadBuffers = ($readBufferSize + $readRndBufferSize + $sortBufferSize + $threadStack + $joinBufferSize + $binlogCacheSize) * $maxConnections;
$perThreadMaxBuffers = ($readBufferSize + $readRndBufferSize + $sortBufferSize + $threadStack + $joinBufferSize + $binlogCacheSize) * $maxUsedConnections;

$innodbBufferPoolSize = $globalVariables['innodb_buffer_pool_size'];
if (empty($innodbBufferPoolSize)) $innodbBufferPoolSize = 0;

$innodbAdditionalMemPoolSize = $globalVariables['innodb_additional_mem_pool_size'];
if (empty($innodbAdditionalMemPoolSize)) $innodbAdditionalMemPoolSize = 0;

$innodbLogBufferSize = $globalVariables['innodb_log_buffer_size'];
if (empty($innodbLogBufferSize)) $innodbLogBufferSize = 0;

$keyBufferSize = $globalVariables['key_buffer_size'];

$queryCacheSize = $globalVariables['query_cache_size'];
if (empty($queryCacheSize)) $queryCacheSize = 0;

$globalBuffers = $innodbBufferPoolSize + $innodbAdditionalMemPoolSize + $innodbLogBufferSize + $keyBufferSize + $queryCacheSize;

$maxMemory = $globalBuffers + $perThreadMaxBuffers;
$totalMemory = $globalBuffers + $perThreadBuffers;

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

/* Query cache */
$queryCacheSize = $globalVariables['query_cache_size'];
$queryCacheLimit = $globalVariables['query_cache_limit'];
$queryCacheMinResUnit = $globalVariables['query_cache_min_res_unit'];
$qcacheFreeMemory = $globalStatus['Qcache_free_memory'];
$qcacheTotalBlocks = $globalStatus['Qcache_total_blocks'];
$qcacheFreeBlocks = $globalStatus['Qcache_free_blocks'];
$qcacheLowmemPrunes = $globalStatus['Qcache_lowmem_prunes'];

$qcacheUsedMemory = $queryCacheSize - $qcacheFreeMemory;
$qcacheMemFillRatio = $qcacheUsedMemory * 100 / $queryCacheSize;

/* Sort operations */
$sortMergePasses = $globalStatus['Sort_merge_passes'];
$sortScan = $globalStatus['Sort_scan'];
$sortRange = $globalStatus['Sort_range'];
$sortBufferSize = $globalVariables['sort_buffer_size'];
$readRndBufferSize = $globalVariables['read_rnd_buffer_size'];
$totalSorts = $sortScan + $sortRange;

/* Joins */
$selectFullJoin = $globalStatus['Select_full_join'];
$selectRangeCheck = $globalStatus['Select_range_check'];
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
        .table-sm td {
            font-size: .9em;
            padding: 1px;
        }
    </style>
</head>
<body>
<div class='container'>
    <nav class="navbar navbar-light bg-light">
        <a class="navbar-brand" href="#">MySQL-Tuner-PHP</a>
    </nav>
    <div class='row'>
        <div class='col-sm'>
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
        <div class='col-sm'>
            <table class='table table-sm'>
                <tr>
                    <td>Server version:</td>
                    <td><?= $version ?></td>
                </tr>
                <tr>
                    <td>Major version:</td>
                    <td><?= $majorVersion ?></td>
                </tr>
            </table>
        </div>
        <div class='col-sm'>
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
        <div class='col-sm-12'>
            <div class='card'>
                <div class='card-header'>Slow queries</div>
                <div class='card-body'>
                    <p>The slow query log is a record of SQL queries that took a long time to perform. Note that, if
                        your queries contain user's passwords, the slow query log may contain passwords too. Thus, it
                        should be protected.</p>
                    <?php
                    if ($slowQueryLog == "ON") {
                        ?>
                        <div class="alert alert-success" role="alert">
                            Slow query log is enabled!
                        </div>
                        <?php
                    }
                    elseif ($slowQueryLog == "OFF" or empty($logSlowQueries)) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            Slow query log is not enabled!
                        </div>
                        <?php
                    }
                    ?>
                    <p>Long query time: <?= $longQueryTime ?></p>
                    <p>Slow query log: <?= $slowQueryLog ?></p>
                    <p>Slow query log file: <?= $slowQueryLogFile ?></p>
                    <p>Since startup, <?= $slowQueries ?> out of <?= $questions ?> have taken longer
                        than <?= $longQueryTime ?> sec. to complete.</p>

                    <?php
                    if ($longQueryTime > $preferredQueryTime) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Your long_query_time may be too high, I typically set this under <?= $preferredQueryTime ?>
                            sec.
                        </div>
                        <?php
                    }
                    elseif (round($longQueryTime) == 0) {
                        ?>
                        <div class="alert alert-warning" role="alert">
                            Your long_query_time ios set to zero, which will cause ALL queries to be logged!<br>
                            If you actually want to log all queries, use the query log, not the slow query log.
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Binary log
            </div>
            <div class='card-body'>
                
                <?php
                if ($logBin == "ON") {
                    ?>
                    <div class="alert alert-success" role="alert">
                        The binary log is enabled!
                    </div>
                    <?php
                }
                else {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        The binary log is not enabled.
                    </div>
                    <?php
                }
                ?>
                <p>Log bin: <?= $logBin ?></p>
                <p>Max binlog size: <?= $maxBinlogSize ?></p>
                <p>Expire logs days: <?= $expireLogsDays ?></p>
                <p>Sync binlog: <?= $syncBinlog ?></p>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Threads
            </div>
            <div class='card-body'>
                <p>Thread cache size: <?= $threadsCacheSize ?></p>
                <p>Threads cached: <?= $threadsCached ?></p>
                <p>Historic threads per sec: <?= round($historicThreadsPerSec, 4) ?></p>
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Used connections
            </div>
            <div class='card-body'>
                <p>Max connections: <?= $maxConnections ?></p>
                <p>Threads connected: <?= $threadsConnected ?></p>
                <p>Historic max used connections: <?= $maxUsedConnections ?></p>
                <p>Connections ratio: <?php echo round($connectionsRatio, 1); ?> %</p>
                <?php
                if ($connectionsRatio > 85) {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        You should raise max_connections
                    </div>
                    <?php
                }
                elseif ($connectionsRatio < 10) {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        You are using less than 10% of your configured max_connections. Lowering max_connections could
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                InnoDB
            </div>
            <div class='card-body'>
                <p>InnoDB buffer pool size: <?= human_readable($innodbBufferPoolSize) ?></p>
                <p>InnoDB additional mem pool size: <?= $innodbAdditionalMemPoolSize ?></p>
                <p>InnoDB fast shutdown: <?= $innodbFastShutdown ?></p>
                <p>InnoDB flush log at trx commit: <?= $innodbFlushLogAtTrxCommit ?></p>
                <p>InnoDB locks unsafe for binlog: <?= $innodbLocksUnsafeForBinlog ?></p>
                <p>InnoDB log buffer size: <?= human_readable($innodbLogBufferSize) ?></p>
                <p>InnoDB log file size: <?= human_readable($innodbLogFileSize) ?></p>
                <p>InnoDB log files in group: <?= $innodbLogFilesInGroup ?></p>
                <p>InnoDB safe binlog: <?= $innodbSafeBinlog ?></p>
                <p>InnoDB thread concurrency: <?= $innodbThreadConcurrency ?></p>
                <?php
                if (!empty($innodbIndexLength)) {
                    ?>
                    <p>InnoDB buffer pool pages data: <?= $innodbBufferPoolPagesData ?></p>
                    <p>InnoDB buffer pool pages misc: <?= $innodbBufferPoolPagesMisc ?></p>
                    <p>InnoDB buffer pool pages free: <?= $innodbBufferPoolPagesFree ?></p>
                    <p>InnoDB buffer pool pages total: <?= $innodbBufferPoolPagesTotal ?></p>
                    <p>InnoDB buffer pool read ahead seq: <?= $innodbBufferPoolReadAheadSeq ?></p>
                    <p>InnoDB buffer pool read requests: <?= $innodbBufferPoolReadRequests ?></p>
                    <p>InnoDB os log pending fsyncs: <?= $innodbOsLogPendingFsyncs ?></p>
                    <p>InnoDB os log pending writes: <?= $innodbOsLogPendingWrites ?></p>
                    <p>InnoDB log waits: <?= $innodbLogWaits ?></p>
                    <p>InnoDB row lock time: <?= $innodbRowLockTime ?></p>
                    <p>InnoDB row lock waits: <?= $innodbRowLockWaits ?></p>
                    <p>InnoDB index space: <?= human_readable($innodbIndexLength) ?></p>
                    <p>InnoDB data space: <?= human_readable($innodbDataLength) ?></p>
                    <p>InnoDB buffer pool free pct.: <?= round($innodbBufferPoolFreePct, 1) ?> %</p>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Memory usage
            </div>
            <div class='card-body'>
                <p>Read buffer size: <?= $readBufferSize ?></p>
                <p>Read rnd buffer size: <?= $readRndBufferSize ?></p>
                <p>Sort buffer size: <?= $sortBufferSize ?></p>
                <p>Thread stack: <?= $threadStack ?></p>
                <p>Max connections: <?= $maxConnections ?></p>
                <p>Join buffer size: <?= $joinBufferSize ?></p>
                <p>Tmp table size: <?= $tmpTableSize ?></p>
                <p>Max heap table size: <?= $maxHeapTableSize ?></p>
                <p>Log bin: <?= $logBin ?></p>
                <p>Max used connections: <?= $maxUsedConnections ?></p>
                <p>Binlog cache size: <?= $binlogCacheSize ?></p>
                <p>Effective tmp table size: <?= $effectiveTmpTableSize ?></p>
                <p>Per thread buffers: <?= human_readable($perThreadBuffers) ?></p>
                <p>Per thread max buffers: <?= $perThreadMaxBuffers ?></p>
                <p>InnoDB buffer pool size: <?= $innodbBufferPoolSize ?></p>
                <p>InnoDB additional mem pool size: <?= $innodbAdditionalMemPoolSize ?></p>
                <p>InnoDB log buffer size: <?= $innodbLogBufferSize ?></p>
                <p>Key buffer size: <?= $keyBufferSize ?></p>
                <p>Query cache size: <?= $queryCacheSize ?></p>
                <p>Global buffers: <?= human_readable($globalBuffers) ?></p>
                <p>Max memory: <?= human_readable($maxMemory) ?></p>
                <p>Total memory: <?= human_readable($totalMemory) ?></p>
                <p>Pct of sys mem: <?= $pctOfSysMem ?> %</p>
                <p>Physical Memory: <?php echo human_readable($physicalMemory); ?></p>
                <?php
                if ($pctOfSysMem > 90) {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        Max memory limit exceeds 90% of physical memory
                    </div>
                    <?php
                }
                else {
                    ?>
                    <div class="alert alert-success" role="alert">
                        Max memory limit seem to be within acceptable norms
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Key buffer
            </div>
            <div class='card-body'>
                <p>Key read requests: <?= $keyReadRequests ?></p>
                <p>Key reads: <?= $keyReads ?></p>
                <p>Key blocks used: <?= $keyBlocksUsed ?></p>
                <p>Key blocks unused: <?= $keyBlocksUnused ?></p>
                <p>Key cache block size: <?= $keyCacheBlockSize ?></p>
                <p>Key buffer size: <?= $keyBufferSize ?></p>
                <p>Data dir: <?= $dataDir ?></p>
                <p>Version compile machine: <?= $versionCompileMachine ?></p>
                <?php
                if ($keyReads == 0) {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        No key reads?! Seriously look into using some indexes
                    </div>
                    <?php
                }
                ?>
                <p>Key cache miss rate is 1 : <?php echo $keyCacheMissRate; ?></p>
                <p>Key buffer free ratio: <?php echo $keyBufferFree; ?></p>
                <?php
                if ($keyCacheMissRate <= 100 && $keyCacheMissRate > 0 && $keyBufferFree < 20) {
                    ?>
                    <div class="alert alert-warning" role="alert">
                        You could increate key_buffer_size. It is safe to raise this up to 1/4 of total system memory.
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Query cache
            </div>
            <div class='card-body'>
                <p>Query cache size: <?= $queryCacheSize ?></p>
                <p>Query cache limit: <?= $queryCacheLimit ?></p>
                <p>Query cache min res unit: <?= $queryCacheMinResUnit ?></p>
                <p>Qcache free memory: <?= $qcacheFreeMemory ?></p>
                <p>Qcache total blocks: <?= $qcacheTotalBlocks ?></p>
                <p>Qcache free blocks: <?= $qcacheFreeBlocks ?></p>
                <p>Qcache lowmem prunes: <?= $qcacheLowmemPrunes ?></p>
                <?php
                if ($queryCacheSize == 0) {
                    ?>
                    <div class="alert alert-info" role="alert">
                        Query cache is supported but not enabled. Perhaps you should set the query_cache_size
                    </div>
                    <?php
                }
                ?>
                <p>Query cache used memory: <?= $qcacheUsedMemory ?></p>
                <p>Query cache mem fill ratio: <?= $qcacheMemFillRatio ?></p>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Sort operations
            </div>
            <div class='card-body'>
                <p>Sort merge passes: <?= $sortMergePasses ?></p>
                <p>Sort scan: <?= $sortScan ?></p>
                <p>Sort range: <?= $sortRange ?></p>
                <p>Sort buffer size: <?= human_readable($sortBufferSize) ?></p>
                <p>Read rnd buffer size: <?= human_readable($readRndBufferSize) ?></p>
                <p>Total sorts: <?= $totalSorts ?></p>
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Joins
            </div>
            <div class='card-body'>
                <p>Select full join: <?= $selectFullJoin ?></p>
                <p>Select range check: <?= $selectRangeCheck ?></p>
                <p>Join buffer size: <?= human_readable($joinBufferSize) ?></p>
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
                            If you are unable to optimize your queries you may want to increase your join_buffer_size to
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Open files limit
            </div>
            <div class='card-body'>
                <p>Open files limit: <?= $openFilesLimit ?></p>
                <p>Open files: <?= $openFiles ?></p>
                <p>Open files ratio: <?= $openFilesRatio ?></p>
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Table cache
            </div>
            <div class='card-body'>
                <p>Datadir: <?= $dataDir ?></p>
                <p>Table cache: <?= $tableCache ?></p>
                <p>Table open cache: <?= $tableOpenCache ?></p>
                <p>Table definition cache: <?= $tableDefinitionCache ?></p>
                <p>Open tables: <?= $openTables ?></p>
                <p>Opened tables: <?= $openedTables ?></p>
                <p>Open table definitions: <?= $openTableDefinitions ?></p>
                <p>Table count: <?= $tableCount ?></p>
                <p>Table cache hit rate: <?= $tableCacheHitRate ?></p>
                <p>Table cache fill: <?= $tableCacheFill ?></p>
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Temp tables
            </div>
            <div class='card-body'>
                <p>Created tmp tables: <?= $createdTmpTables ?></p>
                <p>Created tmp disk tables: <?= $createdTmpDiskTables ?></p>
                <p>Tmp table size: <?= human_readable($tmpTableSize) ?></p>
                <p>Max heap table size: <?= human_readable($maxHeapTableSize) ?></p>
                <p>Tmp disk tables: <?= round($tmpDiskTables, 1) ?></p>
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
                        Perhaps you should increase your tmp_table_size and/or max_heap_table_size to reduce the number
                        of disk-based temperary tables.<br>
                        Note! BLOB and TEXT colums are now allowed in memory tables. If you are using there columns
                        raising these values might not impact your ratio of on disk temp tables.
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Table scans
            </div>
            <div class='card-body'>
                <p>Com select: <?= $comSelect ?></p>
                <p>Handler read rnd next: <?= $handlerReadRndNext ?></p>
                <p>Read buffer size: <?= human_readable($readBufferSize) ?></p>
                <?php
                if ($comSelect > 0) {
                    ?>
                    <p>Full table scans ratio: <?= $fullTableScans ?> : 1</p>
                    <?php

                    if ($fullTableScans >= 4000 && $readBufferSize < 2 * 1024 * 1024) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            You have a high ratio of sequential access requests to SELECTs. You may benefit from raising
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
    <div class='row'>
        <div class='card'>
            <div class='card-header'>
                Table locking
            </div>
            <div class='card-body'>
                <p>Table locks waited: <?= $tableLocksWaited ?></p>
                <p>Table locks immediate: <?= $tableLocksImmediate ?></p>
                <p>Concurrent insert: <?= $concurrentInsert ?></p>
                <p>Low priority updates: <?= $lowPriorityUpdates ?></p>
                <?php
                if ($tableLocksWaited > 0) {
                    ?>
                    <p>Lock / wait ratio: 1 : <?= round($immediateLocksMissRate) ?></p>
                    <?php
                }
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

<h1>GLOBAL STATUS</h1>
<a class="btn btn-primary" data-toggle="collapse" href="#collapseStatus" role="button" aria-expanded="false"
   aria-controls="collapseStatus">Show status</a>
<table class='table table-sm collapse' id='collapseStatus'>
    <?php
    foreach ($globalStatus as $globalStatusName => $globalStatusValue) {
        echo "<tr><td>" . $globalStatusName . "</td><td>" . $globalStatusValue . "</td></tr>\n";
    }
    ?>
</table>
<h1>GLOBAL VARIABLES</h1>
<a class="btn btn-primary" data-toggle="collapse" href="#collapseVariables" role="button" aria-expanded="false"
   aria-controls="collapseVariables">Show variables</a>
<table class='table table-sm collapse' id='collapseVariables'>
    <?php
    foreach ($globalVariables as $globalVariableName => $globalVariableValue) {
        echo "<tr><td>" . $globalVariableName . "</td><td>" . $globalVariableValue . "</td></tr>\n";
    }
    ?>
</table>
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
