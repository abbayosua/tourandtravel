UPDATE flight_schedules SET departure_date = CURDATE() + INTERVAL FLOOR(RAND()*14) DAY WHERE departure_date < CURDATE();
