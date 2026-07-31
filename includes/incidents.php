<?php
require_once __DIR__ . '/db.php';

const VALID_INCIDENT_STATUSES = ['open', 'investigating', 'resolved', 'closed'];

/** Submits a new incident report and assigns a unique tracking code like INC-2026-0001. */
function submitIncident(int $reportedBy, string $type, string $location, string $description): array
{
    $type        = sanitise($type);
    $location    = sanitise($location);
    $description = trim($description);

    if ($type === '' || $location === '' || $description === '') {
        return ['success' => false, 'message' => 'All fields are required.'];
    }

    $db = getDB();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO incidents (tracking_code, reported_by, incident_type, location, description, status)
             VALUES ('PENDING', ?, ?, ?, ?, 'open')"
        );
        $stmt->execute([$reportedBy, $type, $location, $description]);
        $incidentId = (int) $db->lastInsertId();

        $trackingCode = sprintf('INC-%s-%04d', date('Y'), $incidentId);
        $db->prepare('UPDATE incidents SET tracking_code = ? WHERE incident_id = ?')
           ->execute([$trackingCode, $incidentId]);

        $db->commit();
        return ['success' => true, 'tracking_code' => $trackingCode, 'message' => "Incident reported. Your tracking code is {$trackingCode}."];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Failed to submit the incident. Please try again.'];
    }
}

/** Updates the status of an incident (admin / security officer only — enforced by the caller). */
function updateIncidentStatus(int $incidentId, string $status): bool
{
    if (!in_array($status, VALID_INCIDENT_STATUSES, true)) {
        return false;
    }
    $stmt = getDB()->prepare('UPDATE incidents SET status = ? WHERE incident_id = ?');
    return $stmt->execute([$status, $incidentId]);
}

function statusBadgeClass(string $status): string
{
    return [
        'open'          => 'bg-danger',
        'investigating' => 'bg-warning text-dark',
        'resolved'      => 'bg-success',
        'closed'        => 'bg-secondary',
    ][$status] ?? 'bg-secondary';
}
