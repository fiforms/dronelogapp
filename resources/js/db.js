import Dexie from 'dexie';

export const db = new Dexie('DroneLogDB');

db.version(1).stores({
    flights: [
        '++id',
        'client_uuid',
        'server_id',
        'started_at',
        'synced',
        'drone_id',
        'battery_id',
    ].join(', '),

    drones:              '++id, server_id',
    batteries:           '++id, server_id',
    accessories:         '++id, server_id',
    checklist_templates: '++id, server_id',
    checklist_items:     '++id, server_id, template_id',
});

// v2: adds status field index to flights; adds risk_items cache table
db.version(2).stores({
    flights: [
        '++id',
        'client_uuid',
        'server_id',
        'started_at',
        'synced',
        'drone_id',
        'battery_id',
        'status',
    ].join(', '),

    drones:              '++id, server_id',
    batteries:           '++id, server_id',
    accessories:         '++id, server_id',
    checklist_templates: '++id, server_id',
    checklist_items:     '++id, server_id, template_id',
    risk_items:          '++id, server_id',
});

export default db;
