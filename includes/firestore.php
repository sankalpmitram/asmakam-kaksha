<?php
/**
 * firestore.php
 * A minimal, dependency-free Firestore REST API client for PHP.
 *
 * Auth: uses a Google service account (JSON key) to build a signed JWT,
 * exchanges it for an OAuth2 access token (cached to a temp file until
 * it expires), then calls the Firestore REST API with that token.
 *
 * This file only knows how to talk to Firestore's document API
 * (list / get / set / delete + Firestore's typed value format).
 * includes/functions.php wraps these into the same read_json()/write_json()
 * interface the rest of the app already uses, so no other file needs to
 * change.
 */

// One access token is used for both Firestore (app data) and Cloud
// Storage (student photos / school logo uploads).
define('FIRESTORE_SCOPE', 'https://www.googleapis.com/auth/datastore https://www.googleapis.com/auth/devstorage.read_write');
define('FIRESTORE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('FIRESTORE_TOKEN_CACHE_FILE', sys_get_temp_dir() . '/asmakam_kaksha_firestore_token.json');

/**
 * Loads the service account credentials.
 * Looks first for env vars (recommended for Render), then falls back to
 * a local JSON key file for local development.
 *
 * Env vars used:
 *   FIREBASE_PROJECT_ID
 *   FIREBASE_CLIENT_EMAIL
 *   FIREBASE_PRIVATE_KEY   (with literal \n for newlines, as Render stores it)
 *
 * Local file fallback: /firebase-service-account.json at the project root
 * (this file is gitignored — never commit it).
 */
function firestore_credentials() {
    static $creds = null;
    if ($creds !== null) return $creds;

    $projectId = getenv('FIREBASE_PROJECT_ID');
    $clientEmail = getenv('FIREBASE_CLIENT_EMAIL');
    $privateKey = getenv('FIREBASE_PRIVATE_KEY');

    if ($projectId && $clientEmail && $privateKey) {
        $creds = [
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            // Render stores multi-line env vars with literal "\n" — convert back.
            'private_key' => str_replace('\n', "\n", $privateKey),
        ];
        return $creds;
    }

    $keyFile = __DIR__ . '/../firebase-service-account.json';
    if (file_exists($keyFile)) {
        $json = json_decode(file_get_contents($keyFile), true);
        if ($json && isset($json['project_id'], $json['client_email'], $json['private_key'])) {
            $creds = [
                'project_id' => $json['project_id'],
                'client_email' => $json['client_email'],
                'private_key' => $json['private_key'],
            ];
            return $creds;
        }
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Firebase कॉन्फ़िगरेशन नहीं मिला। FIREBASE_PROJECT_ID / FIREBASE_CLIENT_EMAIL / FIREBASE_PRIVATE_KEY सेट करें, या firebase-service-account.json जोड़ें।',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Base64url encode (JWT uses this instead of plain base64).
 */
function firestore_base64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Builds a signed JWT assertion for the Google OAuth2 JWT-bearer flow.
 */
function firestore_build_jwt($creds) {
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claim = [
        'iss' => $creds['client_email'],
        'scope' => FIRESTORE_SCOPE,
        'aud' => FIRESTORE_TOKEN_URL,
        'exp' => $now + 3600,
        'iat' => $now,
    ];
    $segments = [
        firestore_base64url(json_encode($header)),
        firestore_base64url(json_encode($claim)),
    ];
    $signingInput = implode('.', $segments);

    $signature = '';
    $ok = openssl_sign($signingInput, $signature, $creds['private_key'], 'sha256WithRSAEncryption');
    if (!$ok) {
        json_response(false, 'JWT साइन करने में त्रुटि हुई। Private key जाँचें।', null, 500);
    }
    $segments[] = firestore_base64url($signature);
    return implode('.', $segments);
}

/**
 * Returns a valid OAuth2 access token, using a short-lived file cache so
 * we don't re-authenticate on every single request.
 */
function firestore_get_access_token() {
    if (file_exists(FIRESTORE_TOKEN_CACHE_FILE)) {
        $cached = json_decode(file_get_contents(FIRESTORE_TOKEN_CACHE_FILE), true);
        if ($cached && isset($cached['access_token'], $cached['expires_at']) && $cached['expires_at'] > time() + 30) {
            return $cached['access_token'];
        }
    }

    $creds = firestore_credentials();
    $jwt = firestore_build_jwt($creds);

    $ch = curl_init(FIRESTORE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 15,
    ]);
   $response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['access_token'])) {
    json_response(
        false,
        'Firebase Error',
        [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response' => $response
        ],
        500
    );
}

@file_put_contents(FIRESTORE_TOKEN_CACHE_FILE, json_encode([
    'access_token' => $data['access_token'],
    'expires_at' => time() + (int)($data['expires_in'] ?? 3600),
]));

return $data['access_token'];
}
/**
 * Low-level HTTP request to the Firestore REST API.
 */
function firestore_request($method, $path, $bodyArray = null) {
    $creds = firestore_credentials();
    $token = firestore_get_access_token();
    $url = 'https://firestore.googleapis.com/v1/projects/' . $creds['project_id'] . '/databases/(default)/documents' . $path;

    $ch = curl_init($url);
    $headers = ['Authorization: Bearer ' . $token, 'Content-Type: application/json'];
    $opts = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ];
    if ($bodyArray !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($bodyArray, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        json_response(false, 'Firestore से संपर्क नहीं हो सका: ' . $err, null, 502);
    }

    $decoded = json_decode($response, true);
    return ['code' => $httpCode, 'body' => $decoded];
}

/* ---------------- Value <-> PHP conversion ---------------- */

function firestore_is_list($arr) {
    if (!is_array($arr)) return false;
    return array_keys($arr) === range(0, count($arr) - 1);
}

function firestore_encode_value($value) {
    if ($value === null) return ['nullValue' => null];
    if (is_bool($value)) return ['booleanValue' => $value];
    if (is_int($value)) return ['integerValue' => (string)$value];
    if (is_float($value)) return ['doubleValue' => $value];
    if (is_array($value)) {
        if (firestore_is_list($value)) {
            return ['arrayValue' => ['values' => array_map('firestore_encode_value', $value)]];
        }
        return ['mapValue' => ['fields' => firestore_encode_fields($value)]];
    }
    return ['stringValue' => (string)$value];
}

function firestore_encode_fields($assoc) {
    $fields = [];
    foreach ($assoc as $key => $val) {
        $fields[$key] = firestore_encode_value($val);
    }
    return $fields;
}

function firestore_decode_value($value) {
    if (!is_array($value)) return null;
    if (array_key_exists('stringValue', $value)) return $value['stringValue'];
    if (array_key_exists('integerValue', $value)) return (int)$value['integerValue'];
    if (array_key_exists('doubleValue', $value)) return (float)$value['doubleValue'];
    if (array_key_exists('booleanValue', $value)) return (bool)$value['booleanValue'];
    if (array_key_exists('nullValue', $value)) return null;
    if (array_key_exists('timestampValue', $value)) return $value['timestampValue'];
    if (array_key_exists('mapValue', $value)) {
        return firestore_decode_fields($value['mapValue']['fields'] ?? []);
    }
    if (array_key_exists('arrayValue', $value)) {
        $items = $value['arrayValue']['values'] ?? [];
        return array_map('firestore_decode_value', $items);
    }
    return null;
}

function firestore_decode_fields($fields) {
    $assoc = [];
    foreach ((array)$fields as $key => $val) {
        $assoc[$key] = firestore_decode_value($val);
    }
    return $assoc;
}

/**
 * Extracts the trailing document ID from a Firestore document "name" path.
 */
function firestore_doc_id_from_name($name) {
    $parts = explode('/', $name);
    return end($parts);
}

/* ---------------- Collection-level helpers ---------------- */

/**
 * Lists every document in a collection as [ ['id' => ..., ...fields], ... ].
 * Handles pagination automatically.
 */
function firestore_list($collection) {
    $items = [];
    $pageToken = null;
    do {
        $query = ['pageSize' => '300'];
        if ($pageToken) $query['pageToken'] = $pageToken;
        $res = firestore_request('GET', '/' . $collection . '?' . http_build_query($query));
        if ($res['code'] === 404 || empty($res['body']['documents'])) {
            break;
        }
        foreach ($res['body']['documents'] as $doc) {
            $decoded = firestore_decode_fields($doc['fields'] ?? []);
            $docId = firestore_doc_id_from_name($doc['name']);
            $decoded['id'] = ctype_digit($docId) ? (int)$docId : $docId;
            $items[] = $decoded;
        }
        $pageToken = $res['body']['nextPageToken'] ?? null;
    } while ($pageToken);

    return $items;
}

/**
 * Gets a single document by ID. Returns null if it doesn't exist.
 */
function firestore_get($collection, $id) {
    $res = firestore_request('GET', '/' . $collection . '/' . rawurlencode((string)$id));
    if ($res['code'] !== 200) return null;
    $decoded = firestore_decode_fields($res['body']['fields'] ?? []);
    $decoded['id'] = ctype_digit((string)$id) ? (int)$id : $id;
    return $decoded;
}

/**
 * Creates or fully overwrites a document (Firestore PATCH without an
 * updateMask replaces the whole document, and creates it if missing).
 */
function firestore_set($collection, $id, $dataAssoc) {
    $fields = firestore_encode_fields($dataAssoc);
    $res = firestore_request('PATCH', '/' . $collection . '/' . rawurlencode((string)$id), ['fields' => $fields]);
    return $res['code'] === 200;
}

/**
 * Deletes a document. Not an error if it doesn't exist.
 */
function firestore_delete($collection, $id) {
    $res = firestore_request('DELETE', '/' . $collection . '/' . rawurlencode((string)$id));
    return in_array($res['code'], [200, 404], true);
}
