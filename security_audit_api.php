<?php
/**
 * Security Audit API Endpoint
 * 
 * Returns security audit results as JSON
 */

require_once __DIR__ . '/security_audit.php';

header('Content-Type: application/json');
echo json_encode(SecurityAudit::getAuditResults(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
