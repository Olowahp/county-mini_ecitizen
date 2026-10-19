<?php
declare(strict_types=1);

function initialize_database(DatabaseConnection $db): void
{
    // The schema is imported once from database/muranga_citizen.sql.
    // Seed initial categories and editable licence rates only when empty.
    $categoryCount = (int)$db->query('SELECT COUNT(*) AS total FROM categories')->fetchColumn();
    if ($categoryCount === 0) {
        $categories = [
            ['Water and Sanitation', 'Water supply, meter, billing, sewerage and sanitation concerns'],
            ['Roads and Streetlights', 'Road repairs, street lighting and drainage'],
            ['Garbage and Environment', 'Waste collection, dumping and environmental concerns'],
            ['Health Services', 'County health facilities and public health concerns'],
            ['Business and Trade', 'Markets, business permits and trade services'],
            ['Land and Physical Planning', 'Land administration and development planning'],
            ['Education and Social Services', 'Early childhood education and community support'],
            ['Agriculture and Livestock', 'Agricultural extension and livestock services'],
            ['Public Works', 'Public buildings and county infrastructure'],
            ['Other Government Services', 'Other county or public service requests'],
        ];
        $insert = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        foreach ($categories as $category) $insert->execute($category);
    }
    $rateCount = (int)$db->query('SELECT COUNT(*) AS total FROM business_licence_rates')->fetchColumn();
    if ($rateCount === 0) {
        $rates = [['Small',1,1000],['Small',3,2500],['Small',7,5000],['Medium',1,2500],['Medium',3,6000],['Medium',7,12000],['Large',1,5000],['Large',3,12000],['Large',7,25000]];
        $insert = $db->prepare('INSERT INTO business_licence_rates (business_size,term_years,fee_amount) VALUES (?,?,?)');
        foreach ($rates as $rate) $insert->execute($rate);
    }
    $adminExists = (int)$db->query("SELECT COUNT(*) AS total FROM users WHERE role IN ('admin','super_admin')")->fetchColumn();
    if ($adminExists === 0) {
        $stmt=$db->prepare("INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?, 'super_admin')");
        $stmt->execute(['Portal Administrator','admin@muranga.go.ke','',password_hash('Admin@12345',PASSWORD_DEFAULT)]);
    }
}
