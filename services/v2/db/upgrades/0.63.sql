ALTER TABLE triggers ADD cluster_id INT(32) unsigned AFTER beacon_minor;
ALTER TABLE factories CHANGE location_bound_type location_bound_type ENUM('PLAYER', 'LOCATION', 'CLUSTER');
ALTER TABLE factories ADD cluster_instance_type ENUM('NONE','PLAQUE','ITEM','DIALOG','WEB_PAGE','NOTE','FACTORY','SCENE','EVENT_PACKAGE') AFTER production_timestamp;
ALTER TABLE factories ADD cluster_instance_id INT(32) AFTER cluster_instance_type;
ALTER TABLE factories ADD cluster_radius INT(32) AFTER cluster_instance_id;
ALTER TABLE factories ADD cluster_threshold INT(32) AFTER cluster_radius;
