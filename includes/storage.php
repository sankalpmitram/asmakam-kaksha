<?php
/**
 * storage.php
 * Minimal REST client for Firebase Storage (which is just a Google Cloud
 * Storage bucket under the hood). Reuses the same service-account OAuth
 * token as Firestore (includes/firestore.php) — one JWT, two scopes.
 *
 * Uploaded files (student photos, school logo) are made publicly
 * readable via `predefinedAcl=publicRead` so the app can use their
 * direct https://storage.googleapis.com/... URL as an <img src> with no
 * extra signed-URL logic. Objects are namespaced under folders:
 *   student-photos/xxxx.jpg
 *   school-logos/xxxx.png
 */

define('STORAGE_UPLOAD_BASE', 'https://storage.googleapis.com/upload/storage/v1/b/');
define('STORAGE_API_BASE', 'https://storage.googleapis.com/storage/v1/b/');
define('STORAGE_PUBLIC_BASE', 'https://storage.googleapis.com/');

/**
 * Determines the Firebase Storage bucket name.
 * Override with the FIREBASE_STORAGE_BUCKET env var if your project's
 * bucket doesn't follow the classic "{project_id}.appspot.com" pattern
 * (newer Firebase projects use "{project_id}.firebasestorage.app").
 */
function storage_bucket_name() {
    $override = getenv('FIREBASE_STORAGE_BUCKET');
    if ($override) return $override;
    $creds = firestore_credentials();
    return $creds['project_id'] . '.appspot.com';
}

/**
 * Uploads raw file contents to the bucket and returns its public URL.
 * Returns null on failure (caller should keep any previous value).
 */
function storage_upload_contents($binaryContents, $objectName, $mimeType) {
    $bucket = storage_bucket_name();
    $token = firestore_get_access_token();

    $url = STORAGE_UPLOAD_BASE . rawurlencode($bucket) . '/o'
        . '?uploadType=media'
        . '&name=' . rawurlencode($objectName)
        . '&predefinedAcl=publicRead';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $binaryContents,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: ' . $mimeType,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    return storage_public_url($bucket, $objectName);
}

/**
 * Convenience wrapper: uploads a file already on local disk (e.g. from
 * $_FILES[...]['tmp_name']) and returns its public URL.
 */
function storage_upload_local_file($localPath, $objectName, $mimeType) {
    $contents = file_get_contents($localPath);
    if ($contents === false) return null;
    return storage_upload_contents($contents, $objectName, $mimeType);
}

/**
 * Deletes an object. Safe to call even if it no longer exists.
 */
function storage_delete_object($objectName) {
    if (!$objectName) return true;
    $bucket = storage_bucket_name();
    $token = firestore_get_access_token();
    $url = STORAGE_API_BASE . rawurlencode($bucket) . '/o/' . rawurlencode($objectName);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return in_array($httpCode, [200, 204, 404], true);
}

/**
 * Builds the public download URL for an object in the bucket.
 */
function storage_public_url($bucket, $objectName) {
    return STORAGE_PUBLIC_BASE . $bucket . '/' . $objectName;
}

/**
 * Given a previously-stored public URL, extracts the object name (e.g.
 * "student-photos/abc123.jpg") so the old file can be deleted when a
 * new one is uploaded. Returns null if the URL doesn't match our bucket.
 */
function storage_object_name_from_url($url) {
    if (!$url) return null;
    $bucket = storage_bucket_name();
    $prefix = STORAGE_PUBLIC_BASE . $bucket . '/';
    if (strpos($url, $prefix) === 0) {
        return substr($url, strlen($prefix));
    }
    return null;
}
