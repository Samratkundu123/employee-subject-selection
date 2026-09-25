<?php
// Brainware University Employee Subject Selection System
// Complete System & Security Test Suite
declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';

class TestClient
{
    private string $cookieFile;
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'bwu_cookie_');
    }

    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function request(string $method, string $path, array|string $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't auto-follow so we can inspect 302 redirects

        $customHeaders = $headers;

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($data)) {
                $isJson = in_array('Content-Type: application/json', $headers, true);
                if ($isJson) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if (!empty($customHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
        }

        $rawResponse = curl_exec($ch);
        if ($rawResponse === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("CURL request failed to {$url}: {$err}");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerStr = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);

        // Parse headers
        $parsedHeaders = [];
        foreach (explode("\r\n", $headerStr) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $parsedHeaders[strtolower(trim($k))] = trim($v);
            }
        }

        $jsonData = json_decode($body, true);

        return [
            'status'  => $httpCode,
            'headers' => $parsedHeaders,
            'body'    => $body,
            'json'    => $jsonData
        ];
    }

    public function extractCsrfToken(string $html): string
    {
        if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/window\.BWU_CSRF_TOKEN\s*=\s*"([^"]+)";/', $html, $matches)) {
            return $matches[1];
        }
        return '';
    }
}

function assertTest(bool $condition, string $testName): void
{
    if ($condition) {
        echo " [PASS] " . $testName . PHP_EOL;
    } else {
        echo "❌ [FAIL] " . $testName . PHP_EOL;
        throw new RuntimeException("Assertion failed: {$testName}");
    }
}

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo "  BRAINWARE UNIVERSITY - SYSTEM & INTEGRATION TEST SUITE  " . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

// ----------------------------------------------------
// Test Group 1: Unauthenticated Route Protection
// ----------------------------------------------------
echo "--- Group 1: Security & Route Protection ---" . PHP_EOL;
$anonClient = new TestClient($baseUrl);

// 1.1 Unauthenticated /dashboard redirects to /login
$res = $anonClient->request('GET', '/dashboard');
assertTest($res['status'] === 302 && str_contains($res['headers']['location'] ?? '', '/login'), "Unauthenticated /dashboard redirects to /login");

// 1.2 Unauthenticated /hod/dashboard redirects to /hod/login
$res = $anonClient->request('GET', '/hod/dashboard');
assertTest($res['status'] === 302 && str_contains($res['headers']['location'] ?? '', '/hod/login'), "Unauthenticated /hod/dashboard redirects to /hod/login");

// 1.3 Unauthenticated HOD API returns 401 JSON
$res = $anonClient->request('GET', '/api/hod/faculty');
assertTest($res['status'] === 401 && ($res['json']['success'] ?? null) === false, "Unauthenticated /api/hod/faculty returns HTTP 401 JSON");

// 1.4 Unauthenticated Excel Export redirects
$res = $anonClient->request('GET', '/api/hod/export');
assertTest($res['status'] === 302, "Unauthenticated /api/hod/export redirects");

// ----------------------------------------------------
// Test Group 2: Faculty Authentication
// ----------------------------------------------------
echo PHP_EOL . "--- Group 2: Faculty Authentication ---" . PHP_EOL;
$facultyClient = new TestClient($baseUrl);

// 2.1 Load Faculty Login page and get CSRF token
$res = $facultyClient->request('GET', '/login');
assertTest($res['status'] === 200 && str_contains($res['body'], 'Faculty Portal'), "GET /login returns 200 OK");
$csrfToken = $facultyClient->extractCsrfToken($res['body']);
assertTest(!empty($csrfToken), "CSRF token extracted from login page");

// 2.2 Failed Login with invalid password
$res = $facultyClient->request('POST', '/login', [
    'csrf_token' => $csrfToken,
    'email'      => 'arindam.cs@brainwareuniversity.ac.in',
    'password'   => 'WrongPassword999'
]);
assertTest($res['status'] === 302 && str_contains($res['headers']['location'] ?? '', '/login'), "Wrong password rejected with redirect to /login");

// 2.3 Successful Faculty Login
$res = $facultyClient->request('GET', '/login');
$csrfToken = $facultyClient->extractCsrfToken($res['body']);
$res = $facultyClient->request('POST', '/login', [
    'csrf_token' => $csrfToken,
    'email'      => 'arindam.cs@brainwareuniversity.ac.in',
    'password'   => 'Faculty@123'
]);
assertTest($res['status'] === 302 && str_contains($res['headers']['location'] ?? '', '/dashboard'), "Valid faculty login redirects to /dashboard");

// 2.4 Faculty Dashboard view (Unsubmitted state)
$res = $facultyClient->request('GET', '/dashboard');
assertTest($res['status'] === 200, "GET /dashboard returns 200 OK for logged-in faculty");
assertTest(str_contains($res['body'], 'Dr. Arindam Ghosh'), "Dashboard displays faculty name: Dr. Arindam Ghosh");
assertTest(str_contains($res['body'], 'Selected: <span id="countDisplay">0</span> / 5'), "Initial selection counter is 0 / 5");
assertTest(str_contains($res['body'], 'Database Management System'), "Curriculum subjects rendered on dashboard");

$dashboardCsrf = $facultyClient->extractCsrfToken($res['body']);
assertTest(!empty($dashboardCsrf), "Dashboard contains valid CSRF token for submission");

// ----------------------------------------------------
// Test Group 3: Faculty Role Isolation
// ----------------------------------------------------
echo PHP_EOL . "--- Group 3: Role Isolation & Anti-Tampering ---" . PHP_EOL;

// 3.1 Faculty attempting HOD dashboard
$res = $facultyClient->request('GET', '/hod/dashboard');
assertTest($res['status'] === 302 && str_contains($res['headers']['location'] ?? '', '/hod/login'), "Faculty session cannot access HOD dashboard (redirects to /hod/login)");

// 3.2 Faculty attempting HOD API
$res = $facultyClient->request('GET', '/api/hod/faculty');
assertTest($res['status'] === 401 && ($res['json']['success'] ?? null) === false, "Faculty session receives HTTP 401 on /api/hod/faculty");

// ----------------------------------------------------
// Test Group 4: Submission Validation (Backend Strictness)
// ----------------------------------------------------
echo PHP_EOL . "--- Group 4: Backend Strict Integrity Validation ---" . PHP_EOL;

// 4.1 Reject invalid CSRF token
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => 'tampered_fake_token',
    'subject_ids' => [1, 2, 3, 4, 5]
], ['Content-Type: application/json']);
assertTest($res['status'] === 403, "API rejects invalid CSRF token with HTTP 403");

// 4.2 Reject 0 subjects
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => []
], ['Content-Type: application/json']);
assertTest($res['status'] === 400 && str_contains($res['json']['message'] ?? '', '5 subjects'), "Backend rejects 0 subjects");

// 4.3 Reject 4 subjects
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => [1, 2, 3, 4]
], ['Content-Type: application/json']);
assertTest($res['status'] === 400 && str_contains($res['json']['message'] ?? '', '5 subjects'), "Backend rejects 4 subjects");

// 4.4 Reject 6 subjects
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => [1, 2, 3, 4, 5, 6]
], ['Content-Type: application/json']);
assertTest($res['status'] === 400 && str_contains($res['json']['message'] ?? '', '5 subjects'), "Backend rejects 6 subjects");

// 4.5 Reject duplicate subjects [1, 1, 2, 3, 4]
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => [1, 1, 2, 3, 4]
], ['Content-Type: application/json']);
assertTest($res['status'] === 400 && str_contains($res['json']['message'] ?? '', 'Duplicate'), "Backend rejects duplicate subject selection");

// 4.6 Reject non-existent subject ID
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => [1, 2, 3, 4, 99999]
], ['Content-Type: application/json']);
assertTest($res['status'] === 400 && str_contains($res['json']['message'] ?? '', 'invalid or inactive'), "Backend rejects non-existent subject ID");

// ----------------------------------------------------
// Test Group 5: Valid Submission & Order Preservation
// ----------------------------------------------------
echo PHP_EOL . "--- Group 5: Valid Submission & Database Order ---" . PHP_EOL;

// Selected in exact order: DBMS(1), C Programming(5), DSA(10), Data Mining(15), Cloud Computing(20)
$selectedOrdered = [1, 5, 10, 15, 20];
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => $selectedOrdered
], ['Content-Type: application/json']);

assertTest($res['status'] === 200 && ($res['json']['success'] ?? false) === true, "Valid 5-subject submission returns HTTP 200 OK");
assertTest(!empty($res['json']['submission']['receipt_code']), "Submission receipt code generated: " . ($res['json']['submission']['receipt_code'] ?? ''));

// ----------------------------------------------------
// Test Group 6: Immediate Lock & Resubmission Prevention
// ----------------------------------------------------
echo PHP_EOL . "--- Group 6: Permanent Lock & Anti-Resubmission ---" . PHP_EOL;

// 6.1 Reload /dashboard -> Must render Locked Receipt view
$res = $facultyClient->request('GET', '/dashboard');
assertTest($res['status'] === 200, "GET /dashboard returns 200 OK after submission");
assertTest(str_contains($res['body'], 'Submission Recorded & Locked'), "Dashboard shows: Submission Recorded & Locked");
assertTest(str_contains($res['body'], 'SUBMITTED'), "Status badge: SUBMITTED");
assertTest(str_contains($res['body'], 'Database Management System'), "Receipt contains Subject 1: DBMS");
assertTest(str_contains($res['body'], 'Cloud Computing'), "Receipt contains Subject 5: Cloud Computing");
assertTest(!str_contains($res['body'], 'Review Selection'), "Review Selection button is NOT present in locked view");
assertTest(!str_contains($res['body'], 'Resubmit'), "No resubmit button rendered");

// 6.2 Direct second API submission attempt -> MUST return HTTP 409 Conflict
$res = $facultyClient->request('POST', '/api/faculty/submit', [
    'csrf_token'  => $dashboardCsrf,
    'subject_ids' => [2, 3, 4, 6, 7]
], ['Content-Type: application/json']);
assertTest($res['status'] === 409, "Second submission attempt returns HTTP 409 Conflict");
assertTest(str_contains($res['json']['message'] ?? '', 'already submitted'), "Response confirms: already submitted. Resubmission is not allowed.");

// 6.3 Logout and Login again -> Still locked!
$res = $facultyClient->request('GET', '/logout');
assertTest($res['status'] === 302, "Logout redirects to /login");

// Log back in
$res = $facultyClient->request('GET', '/login');
$newCsrf = $facultyClient->extractCsrfToken($res['body']);
$facultyClient->request('POST', '/login', [
    'csrf_token' => $newCsrf,
    'email'      => 'arindam.cs@brainwareuniversity.ac.in',
    'password'   => 'Faculty@123'
]);
$res = $facultyClient->request('GET', '/dashboard');
assertTest(str_contains($res['body'], 'Submission Recorded & Locked'), "Upon re-login, faculty is STILL locked with final submission receipt");

// ----------------------------------------------------
// Test Group 7: HOD Authentication & Dashboard
// ----------------------------------------------------
echo PHP_EOL . "--- Group 7: HOD Portal, Search, Filter & Excel ---" . PHP_EOL;
$hodClient = new TestClient($baseUrl);

// 7.1 HOD Login
$res = $hodClient->request('GET', '/hod/login');
$hodCsrf = $hodClient->extractCsrfToken($res['body']);
$res = $hodClient->request('POST', '/hod/login', [
    'csrf_token' => $hodCsrf,
    'email'      => 'hod.css@brainwareuniversity.ac.in',
    'password'   => 'gurudev'
]);
assertTest($res['status'] === 302 && str_contains($res['headers']['location'] ?? '', '/hod/dashboard'), "Valid HOD login redirects to /hod/dashboard");

// 7.2 HOD Dashboard view
$res = $hodClient->request('GET', '/hod/dashboard');
assertTest($res['status'] === 200, "GET /hod/dashboard returns 200 OK");
assertTest(str_contains($res['body'], 'HOD Subject Selection Overview'), "Dashboard title present");
assertTest(str_contains($res['body'], 'Download Excel'), "Download Excel button present");

// 7.3 HOD API: All Faculty
$res = $hodClient->request('GET', '/api/hod/faculty?filter=all');
assertTest($res['status'] === 200 && ($res['json']['success'] ?? false) === true, "GET /api/hod/faculty?filter=all returns 200 OK");
$allFaculty = $res['json']['faculty'];
$counts = $res['json']['counts'];
assertTest($counts['total'] === 10, "Total Faculty count = 10");
assertTest($counts['submitted'] === 3, "Submitted Faculty count = 3 (Sneha, Kuntal, Arindam)");
assertTest($counts['pending'] === 7, "Pending Faculty count = 7");

// 7.4 HOD API: Filter Submitted
$res = $hodClient->request('GET', '/api/hod/faculty?filter=submitted');
$subFaculty = $res['json']['faculty'];
assertTest(count($subFaculty) === 3, "Filter 'submitted' returns exactly 3 faculty members");

// 7.5 HOD API: Filter Pending
$res = $hodClient->request('GET', '/api/hod/faculty?filter=pending');
$penFaculty = $res['json']['faculty'];
assertTest(count($penFaculty) === 7, "Filter 'pending' returns exactly 7 faculty members");
assertTest($penFaculty[0]['subject_1'] === '-', "Pending faculty has '-' for Subject 1");
assertTest($penFaculty[0]['status'] === 'Pending', "Pending faculty has status 'Pending'");

// 7.6 HOD API: Search
$res = $hodClient->request('GET', '/api/hod/faculty?search=Arindam');
assertTest(count($res['json']['faculty']) === 1, "Search for 'Arindam' returns exactly 1 record");
assertTest($res['json']['faculty'][0]['subject_1'] === 'Database Management System', "Arindam Subject 1 is Database Management System");
assertTest($res['json']['faculty'][0]['subject_2'] === 'Programming in C', "Arindam Subject 2 is Programming in C");
assertTest($res['json']['faculty'][0]['subject_5'] === 'Cloud Computing', "Arindam Subject 5 is Cloud Computing");

// ----------------------------------------------------
// Test Group 8: Server-Side Excel File Download & Integrity
// ----------------------------------------------------
echo PHP_EOL . "--- Group 8: Excel Generation & Content Inspection ---" . PHP_EOL;

$res = $hodClient->request('GET', '/api/hod/export');
assertTest($res['status'] === 200, "GET /api/hod/export returns HTTP 200 OK");
assertTest(str_contains($res['headers']['content-type'] ?? '', 'spreadsheetml'), "Content-Type is valid OpenXML spreadsheet");
assertTest(str_contains($res['headers']['content-disposition'] ?? '', 'Faculty_Subject_Selection_'), "Content-Disposition has proper filename");

// Verify Excel is a valid ZIP and inspect its OpenXML contents
$tempXlsx = tempnam(sys_get_temp_dir(), 'test_verify_');
file_put_contents($tempXlsx, $res['body']);

$zip = new ZipArchive();
$openResult = $zip->open($tempXlsx);
assertTest($openResult === true, "Excel file is a valid, uncorrupted ZIP archive");

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
assertTest($sheetXml !== false, "ZIP contains worksheet sheet1.xml");
assertTest(str_contains($sheetXml, 'Database Management System'), "Worksheet contains Arindam's submitted subject: Database Management System");
assertTest(str_contains($sheetXml, 'Programming in C'), "Worksheet contains Arindam's submitted subject: Programming in C");
assertTest(str_contains($sheetXml, 'Dr. Arindam Ghosh'), "Worksheet contains Faculty Name: Dr. Arindam Ghosh");
assertTest(str_contains($sheetXml, 'arindam.cs@brainwareuniversity.ac.in'), "Worksheet contains Faculty Email");
assertTest(str_contains($sheetXml, 'frozen'), "Worksheet has frozen header pane");
assertTest(str_contains($sheetXml, 'autoFilter'), "Worksheet has autoFilter enabled");

$zip->close();
@unlink($tempXlsx);

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo " ALL 28 AUTOMATED INTEGRATION & SECURITY TESTS PASSED! " . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;
