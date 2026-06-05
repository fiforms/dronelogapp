const HEADERS = [
    'Date',
    'Start Time',
    'End Time',
    'Duration (min)',
    'Drone Name',
    'Drone Model',
    'Drone Serial',
    'FAA Registration',
    'Battery Name',
    'Battery Capacity (mAh)',
    'Battery % Start',
    'Battery % End',
    'Location Description',
    'Latitude',
    'Longitude',
    'Purpose',
    'Purpose Notes',
    'LAANC Status',
    'LAANC Authorization #',
    'Flight Plan',
    'Post-Flight Notes',
    'Accessories',
    'Retrospective',
    'Client UUID',
];

function escapeCell(value) {
    if (value === null || value === undefined) return '';
    const str = String(value);
    if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return '"' + str.replace(/"/g, '""') + '"';
    }
    return str;
}

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('en-US');
}

function formatTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function flightToRow(flight, drones, batteries) {
    // API-shaped flights have nested objects; local IndexedDB flights use IDs
    const drone = flight.drone ?? drones.find((d) => d.id === flight.drone_id) ?? null;
    const battery = flight.battery ?? batteries.find((b) => b.id === flight.battery_id) ?? null;
    const accessories = (flight.accessories ?? []).map((a) => a.name).filter(Boolean).join('; ');

    return [
        formatDate(flight.started_at),
        formatTime(flight.started_at),
        formatTime(flight.ended_at),
        flight.duration_minutes ?? '',
        drone?.name ?? '',
        drone?.model ?? '',
        drone?.serial ?? '',
        drone?.registration_number ?? '',
        battery?.name ?? '',
        battery?.capacity_mah ?? '',
        flight.battery_pct_start ?? '',
        flight.battery_pct_end ?? '',
        flight.location_description ?? '',
        flight.lat ?? '',
        flight.lng ?? '',
        flight.purpose ?? '',
        flight.purpose_notes ?? '',
        flight.laanc_status ?? '',
        flight.laanc_authorization_number ?? '',
        flight.flight_plan ?? '',
        flight.post_flight_notes ?? '',
        accessories,
        flight.is_retrospective ? 'Yes' : 'No',
        flight.client_uuid ?? '',
    ].map(escapeCell).join(',');
}

export function exportFlightsCsv(flights, { drones = [], batteries = [] } = {}) {
    const rows = [HEADERS.join(',')];
    for (const f of flights) {
        rows.push(flightToRow(f, drones, batteries));
    }

    const csv = rows.join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const date = new Date().toISOString().slice(0, 10);
    a.href = url;
    a.download = `flight-log-${date}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
