<?php
/*
Enhanced Notify-Me System for Offline Page
Handles email collection with multiple storage options and notification capabilities.
*/

// Configuration - move these to a separate config file in production
$CONFIG = [
	'STORE_MODE' => 'file', // 'file', 'mailchimp', or 'both'
	'STORE_FILE' => 'data/notify_emails.txt',
	'BACKUP_FILE' => 'data/notify_emails_backup.txt',
	'LOG_FILE' => 'data/notify_log.txt',
	'MAX_EMAILS_PER_IP' => 3, // Rate limiting
	'SESSION_TIMEOUT' => 3600, // 1 hour

	// MailChimp settings (use environment variables in production)
	'MAILCHIMP_API_KEY' => 'your-mailchimp-api-key-here',
	'MAILCHIMP_LIST_ID' => 'your-list-id-here',

	// Email notification settings
	'SMTP_HOST' => 'smtp.gmail.com',
	'SMTP_PORT' => 587,
	'SMTP_USERNAME' => 'your-email@gmail.com',
	'SMTP_PASSWORD' => 'your-app-password',
	'FROM_EMAIL' => 'hello@conni.com.au',
	'FROM_NAME' => 'Conni Australia'
];

// Include required libraries
require_once 'lib/MailChimp.php';
require_once 'phpmailer/PHPMailerAutoload.php';

// Initialize session for rate limiting
session_start();

/**
 * Log events for debugging and monitoring
 */
function logEvent($message, $email = '')
{
	global $CONFIG;
	$timestamp = date('Y-m-d H:i:s');
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	$logEntry = "[$timestamp] IP: $ip | Email: $email | $message" . PHP_EOL;

	// Ensure log directory exists
	$logDir = dirname($CONFIG['LOG_FILE']);
	if (!is_dir($logDir)) {
		mkdir($logDir, 0755, true);
	}

	file_put_contents($CONFIG['LOG_FILE'], $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Check rate limiting
 */
function checkRateLimit($email)
{
	global $CONFIG;
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

	// Initialize session tracking
	if (!isset($_SESSION['email_submissions'])) {
		$_SESSION['email_submissions'] = [];
		$_SESSION['last_reset'] = time();
	}

	// Reset counter if session timeout exceeded
	if (time() - $_SESSION['last_reset'] > $CONFIG['SESSION_TIMEOUT']) {
		$_SESSION['email_submissions'] = [];
		$_SESSION['last_reset'] = time();
	}

	// Check if IP has exceeded limit
	$submissionCount = $_SESSION['email_submissions'][$ip] ?? 0;
	if ($submissionCount >= $CONFIG['MAX_EMAILS_PER_IP']) {
		logEvent("Rate limit exceeded", $email);
		return false;
	}

	return true;
}

/**
 * Update rate limiting counter
 */
function updateRateLimit()
{
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	$_SESSION['email_submissions'][$ip] = ($_SESSION['email_submissions'][$ip] ?? 0) + 1;
}

/**
 * Check if email already exists in file
 */
function emailExistsInFile($email, $filename)
{
	if (!file_exists($filename)) {
		return false;
	}

	$emails = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	return in_array(strtolower(trim($email)), array_map('strtolower', array_map('trim', $emails)));
}

/**
 * Store email in file
 */
function storeEmailInFile($email)
{
	global $CONFIG;

	// Ensure directory exists
	$dir = dirname($CONFIG['STORE_FILE']);
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}

	// Check for duplicates
	if (emailExistsInFile($email, $CONFIG['STORE_FILE'])) {
		logEvent("Duplicate email attempt", $email);
		return ['status' => 'success', 'message' => 'Email already registered'];
	}

	// Store email with timestamp
	$timestamp = date('Y-m-d H:i:s');
	$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	$entry = strtolower($email) . "|$timestamp|$ip" . PHP_EOL;

	$success = file_put_contents($CONFIG['STORE_FILE'], $entry, FILE_APPEND | LOCK_EX);

	// Create backup
	if ($success && file_exists($CONFIG['STORE_FILE'])) {
		copy($CONFIG['STORE_FILE'], $CONFIG['BACKUP_FILE']);
	}

	if ($success) {
		logEvent("Email stored successfully", $email);
		return ['status' => 'success', 'message' => 'Email registered successfully'];
	} else {
		logEvent("Failed to store email", $email);
		return ['status' => 'error', 'type' => 'FileAccessError', 'message' => 'Unable to store email'];
	}
}

/**
 * Store email in MailChimp
 */
function storeEmailInMailChimp($email)
{
	global $CONFIG;

	if (empty($CONFIG['MAILCHIMP_API_KEY']) || $CONFIG['MAILCHIMP_API_KEY'] === 'your-mailchimp-api-key-here') {
		logEvent("MailChimp not configured", $email);
		return ['status' => 'error', 'type' => 'ConfigurationError', 'message' => 'MailChimp not configured'];
	}

	try {
		$MailChimp = new MailChimp($CONFIG['MAILCHIMP_API_KEY']);

		$result = $MailChimp->call('lists/subscribe', [
			'id' => $CONFIG['MAILCHIMP_LIST_ID'],
			'email' => ['email' => $email],
			'double_optin' => false,
			'update_existing' => true,
			'replace_interests' => false,
			'send_welcome' => false,
		]);

		if (isset($result['email']) && $result['email'] === $email) {
			logEvent("Email stored in MailChimp successfully", $email);
			return ['status' => 'success', 'message' => 'Email registered successfully'];
		} else {
			logEvent("MailChimp error: " . ($result['name'] ?? 'Unknown error'), $email);
			return ['status' => 'error', 'type' => $result['name'] ?? 'MailChimpError', 'message' => 'Registration failed'];
		}
	} catch (Exception $e) {
		logEvent("MailChimp exception: " . $e->getMessage(), $email);
		return ['status' => 'error', 'type' => 'MailChimpException', 'message' => 'Service temporarily unavailable'];
	}
}

// Set response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Handle CORS if needed
if (isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Credentials: true');
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
	exit;
}

// Validate input
if (empty($_POST['email'])) {
	http_response_code(400);
	echo json_encode(['status' => 'error', 'type' => 'ValidationError', 'message' => 'Email is required']);
	exit;
}

$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

if (!$email) {
	logEvent("Invalid email format: " . $_POST['email']);
	http_response_code(400);
	echo json_encode(['status' => 'error', 'type' => 'ValidationError', 'message' => 'Invalid email format']);
	exit;
}

// Check rate limiting
if (!checkRateLimit($email)) {
	http_response_code(429);
	echo json_encode(['status' => 'error', 'type' => 'RateLimitError', 'message' => 'Too many requests. Please try again later.']);
	exit;
}

// Update rate limit counter
updateRateLimit();

// Process based on storage mode
$results = [];
$success = false;

switch ($CONFIG['STORE_MODE']) {
	case 'file':
		$results[] = storeEmailInFile($email);
		$success = $results[0]['status'] === 'success';
		break;

	case 'mailchimp':
		$results[] = storeEmailInMailChimp($email);
		$success = $results[0]['status'] === 'success';
		break;

	case 'both':
		$results[] = storeEmailInFile($email);
		$results[] = storeEmailInMailChimp($email);
		$success = $results[0]['status'] === 'success' || $results[1]['status'] === 'success';
		break;

	default:
		logEvent("Invalid storage mode: " . $CONFIG['STORE_MODE'], $email);
		http_response_code(500);
		echo json_encode(['status' => 'error', 'message' => 'Invalid configuration']);
		exit;
}

// Return response
if ($success) {
	http_response_code(200);
	echo json_encode([
		'status' => 'success',
		'message' => 'Thank you! We\'ll notify you when we\'re back online.'
	]);
} else {
	// Find the most relevant error
	$errorResult = null;
	foreach ($results as $result) {
		if ($result['status'] === 'error') {
			$errorResult = $result;
			break;
		}
	}

	http_response_code(500);
	echo json_encode([
		'status' => 'error',
		'type' => $errorResult['type'] ?? 'UnknownError',
		'message' => $errorResult['message'] ?? 'Registration failed'
	]);
}
