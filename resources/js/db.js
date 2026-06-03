import Dexie from 'dexie';

export const db = new Dexie('DroneLogDB');

db.version(1).stores({
    // Flights: indexed by started_at for 30-day queries; synced (0/1) for pending queue
    flights: [
        '++id',
        'client_uuid',
        'server_id',
        'started_at',
        'synced',
        'drone_id',
        'battery_id',
    ].join(', '),

    // accessories[] and checklist[] are stored as JSON arrays directly on each flight row.
    // This avoids join complexity in IndexedDB at the cost of slightly larger rows.

    drones:              '++id, server_id',
    batteries:           '++id, server_id',
    accessories:         '++id, server_id',
    checklist_templates: '++id, server_id',
    checklist_items:     '++id, server_id, template_id',
});

export default db;
