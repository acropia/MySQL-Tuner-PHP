<?php
$physicalMemory = 1;
function human_readable($number) {
	if ($number > 1024*1024*1024) {
		return round($number / (1024*1024*1024),2)." G";
	}
	elseif ($number > 1024*1024) {
		return round($number / (1024*1024),2)." M";
	}
	elseif ($number > 1024) {
		return round($number / (1024),2)." K";
	}
	else {
		return $number ." bytes";
	}
}
function human_readable_time($seconds) {
	return floor($seconds/86400)." days, ".($seconds/3600%24)." hrs";
}
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Bootstrap CSS -->
	    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

	        <title>Hello, world!</title>
<style>
.table-sm td {
	font-size: .9em;
	padding: 1px;
}
</style>
		  </head>
		    <body>
<?php
$host = '127.0.0.1';
$user = '';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
	$pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
	throw new \PDOException($e->getMessage(), (int)$e->getCode());
}


$globalStatus = [];
$globalVariables = [];

$stmt = $pdo->query('SHOW GLOBAL STATUS');
while ($row = $stmt->fetch()) {
	$globalStatus[$row['Variable_name']] = $row['Value'];
}


$stmt = $pdo->query('show global variables');
while ($row = $stmt->fetch()) {
	$globalVariables[$row['Variable_name']] = $row['Value'];
}

$innodbIndexes = 0;
$stmt = $pdo->query("SELECT IFNULL(SUM(INDEX_LENGTH),0) AS index_count from information_schema.TABLES where ENGINE='InnoDB'");
while ($row = $stmt->fetch()) {
	$innodbIndexes = $row['index_count'];
}
$innodbDataLength = 0;
$stmt = $pdo->query("SELECT IFNULL(SUM(DATA_LENGTH),0) AS data_length from information_schema.TABLES where ENGINE='InnoDB'");
while ($row = $stmt->fetch()) {
	$innodbDataLength = $row['data_length'];
}

?>
<h1>Uptime</h1>
<p>Uptime: <?php echo $globalStatus['Uptime']; ?></p>
<p><?php echo human_readable_time($globalStatus['Uptime']); ?></p>
<p>Avg. qps: <?php echo round($globalStatus['Questions']/$globalStatus['Uptime'],1); ?></p>
<p>Questions: <?php echo $globalStatus['Questions']; ?></p>
<p>Threads: <?php echo $globalStatus['Threads_connected']; ?></p>
<?php
if ($globalStatus['Uptime'] > 172800) {
	echo "Save";
}
else {
	echo "Warning";
}
?>
<h1>Slow queries</h1>
<p>Slow queries: <?php echo $globalStatus['Slow_queries']; ?></p>

<h1>Binary log</h1>
<p>Log bin: <?php echo $globalVariables['log_bin']; ?></p>

<h1>Threads</h1>
<p>Threads created: <?php echo $globalStatus['Threads_created']; ?></p>
<p>Threads cached: <?php echo $globalStatus['Threads_cached']; ?></p>
<p>Thread cache size: <?php echo $globalVariables['thread_cache_size']; ?></p>
<?php
$historicThreadsPerSec = $globalStatus['Threads_created']/$globalStatus['Uptime'];
?>
<p>Historic threads per sec: <?php echo round($historicThreadsPerSec,4); ?></p>

<?php
if ($historicThreadsPerSec > 2) {
	echo "Warning";
}
else {
	echo "Save";
}
?>

<h1>Used connections</h1>
<?php
$maxConnections = $globalVariables['max_connections'];
$maxUsedConnections = $globalStatus['Max_used_connections'];
$threadsConnected = $globalStatus['Threads_connected'];

$connectionsRatio = ($maxUsedConnections*100/$maxConnections);
?>
<p>Max connections: <?php echo $maxConnections; ?></p>
<p>Threads connected: <?php echo $threadsConnected; ?></p>
<p>Historic max used connections: <?php echo $maxUsedConnections; ?></p>
<p>Connections ratio: <?php echo round($connectionsRatio,1); ?></p>

<?php
if ($connectionsRatio > 85) {
	echo "Warning: raise please";
}
elseif ($connectionsRatio < 10) {
	echo "Warning: lower please";
}
else {
	echo "Save";
}
?>

<h1>InnoDB</h1>
<?php
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
?>
<p>InnoDB indexes: <?php echo $innodbIndexes; ?></p>
<?php
if ($innodbIndexes > 0) {
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

	$innodbBufferPoolFreePct = $innodbBufferPoolPagesFree*100/$innodbBufferPoolPagesTotal;

?>
<p>InnoDB buffer pool pages free: <?php echo $innodbBufferPoolPagesFree; ?></p>
<p>InnoDB index space: <?php echo human_readable($innodbIndexes); ?></p>
<p>InnoDB data space: <?php echo human_readable($innodbDataLength); ?></p>
<p>InnoDB buffer pool free: <?php echo round($innodbBufferPoolFreePct,1); ?> %</p>
<?php
}
?>
<p>InnoDB buffer pool size: <?php echo human_readable($innodbBufferPoolSize); ?></p>

<h1>Memory usage</h1>
<?php
$readBufferSize = $globalVariables['read_buffer_size'];
$readRndBufferSize = $globalVariables['read_rnd_buffer_size'];
$sortBufferSize = $globalVariables['sort_buffer_size'];
$threadStack = $globalVariables['thread_stack'];
$maxConnections = $globalVariables['max_connections'];
$joinBufferSize = $globalVariables['join_buffer_size'];
$tmpTableSize = $globalVariables['tmp_table_size'];
$max_heap_table_size = $globalVariables['max_heap_table_size'];
$logBin = $globalVariables['log_bin'];
$maxUsedConnections = $globalStatus['Max_used_connections'];

if ($logBin = "ON") {
	$binlogCacheSize = $globalVariable['binlog_cache_size'];
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

$perThreadBuffers = ($readBufferSize + $readRndBufferSize + $softBufferSize + $threadStack + $joinBufferSize + $binlogCacheSize) * $maxConnections;
$perThreadMaxBuffers = ($readBufferSize + $readRndBufferSize + $softBufferSize + $threadStack + $joinBufferSize + $binlogCacheSize) * $maxUsedConnections;

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

$pctOfSysMem = $totalMemory*100/$physicalMemory;
?>
<p>Mem Memory Ever Allocated: <?php echo human_readable($maxMemory); ?></p>
<p>Configured Max Per-thread Buffers: <?php echo human_readable($perThreadBuffers); ?></p>
<p>Configured Max Global Buffers: <?php echo human_readable($globalBuffers); ?></p>
<p>Configured Max Memory Limit: <?php echo human_readable($totalMemory); ?></p>
<p>Physical Memory: <?php echo human_readable($physicalMemory); ?></p>

<?php
if ($pctOfSysMem > 90) {
	echo "Warning";
}
else {
	echo "Save";
}
?>

<h1>Key buffer size</h1>
<?php
$keyReadRequests = $globalStatus['Key_read_requests'];
$keyReads = $globalStatus['Key_reads'];
$keyBlocksUsed = $globalStatus['Key_blocks_used'];
$keyBlocksUnused = $globalStatus['Key_blocks_unused'];
$keyCacheBlockSize = $globalVariables['key_cache_block_size'];
$keyBufferSize = $globaVariables['key_buffer_size'];
$dataDir = $globaVariables['datadir'];
$versionCompileMachine = $globaVariables['version_compile_machine'];

if ($keyReads == 0) {
	echo "Warning";
	$keyCacheMissRate = 0;
	$keyBufferFree = $keyBlocksUnused * $keyCacheBlockSize / $keyBufferSize * 100;
}
else {
	$keyCacheMissRate = $keyReadRequests / $keyReads;
	if (!$keyBlocksUnused) {
		$keyBufferFree = $keyBlocksUnused * $keyCacheBlockSize / $keyBufferSize * 100;
	}
	else {
		$keyBufferFree = "Unknown";
	}
}
?>
<p>Current key buffer size: <?php echo human_readable($keyBufferSize); ?></p>
<p>Key cache miss rate is 1 : <?php echo $keyCacheMissRate; ?></p>
<p>Key buffer free ratio: <?php echo $keyBufferFree; ?></p>


<h1>GLOBAL STATUS</h1>
<a class="btn btn-primary" data-toggle="collapse" href="#collapseStatus" role="button" aria-expanded="false" aria-controls="collapseStatus">Show status</a>
<table class='table table-sm collapse' id='collapseStatus'>
<?php
foreach ($globalStatus as $globalStatusName=>$globalStatusValue) {
	echo "<tr><td>".$globalStatusName."</td><td>".$globalStatusValue."</td></tr>\n";
}
?>
</table>
<h1>GLOBAL VARIABLES</h1>
<a class="btn btn-primary" data-toggle="collapse" href="#collapseVariables" role="button" aria-expanded="false" aria-controls="collapseVariables">Show variables</a>
<table class='table table-sm collapse' id='collapseVariables'>
<?php
foreach ($globalVariables as $globalVariableName=>$globalVariableValue) {
	echo "<tr><td>".$globalVariableName."</td><td>".$globalVariableValue."</td></tr>\n";
}
?>
</table>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>
