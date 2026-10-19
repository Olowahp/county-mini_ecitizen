<?php
declare(strict_types=1);

/** Small mysqli-backed adapter used by the portal; no PDO extension is required. */
final class PortalDatabase
{
    public mysqli $connection;
    public function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->connection = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], (int)($config['port'] ?? 3306));
        $this->connection->set_charset('utf8mb4');
    }
    public function exec(string $sql): int
    {
        $count = 0;
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->connection->query($statement);
            $count += $this->connection->affected_rows;
        }
        return $count;
    }
    public function query(string $sql): PortalStatement
    {
        $result = $this->connection->query($sql);
        return new PortalStatement($result instanceof mysqli_result ? $result : false);
    }
    public function prepare(string $sql): PortalStatement
    {
        return new PortalStatement($this->connection->prepare($sql));
    }
    public function lastInsertId(): string { return (string)$this->connection->insert_id; }
}

final class PortalStatement
{
    private mysqli_stmt|mysqli_result|false $statement;
    private ?mysqli_result $result = null;
    private array $rows = [];
    public function __construct(mysqli_stmt|mysqli_result|false $statement) { $this->statement = $statement; if ($statement instanceof mysqli_result) $this->result = $statement; }
    public function execute(array $params = []): bool
    {
        if (!$this->statement instanceof mysqli_stmt) return true;
        if ($params) {
            $types = '';
            $values = [];
            foreach ($params as $value) { $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : (is_null($value) ? 's' : 's')); $values[] = $value; }
            $refs = [&$types]; foreach ($values as $key => &$value) $refs[] = &$value;
            $this->statement->bind_param(...$refs);
        }
        $this->statement->execute();
        $this->result = $this->statement->get_result();
        return true;
    }
    public function fetch(): array|false { return $this->result ? ($this->result->fetch_assoc() ?: false) : false; }
    public function fetchAll(): array { return $this->result ? $this->result->fetch_all(MYSQLI_ASSOC) : []; }
    public function rowCount(): int { return $this->statement instanceof mysqli_stmt ? $this->statement->affected_rows : 0; }
    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->fetch(); if ($row === false) return false;
        $values = array_values($row); return $values[$column] ?? false;
    }
}
