<?php
declare(strict_types=1);

/** Thin MySQLi wrapper used by the portal. Requires PHP's mysqli extension. */
final class DatabaseConnection
{
    private mysqli $connection;
    public function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->connection = new mysqli(
            (string)$config['db_host'], (string)$config['db_user'],
            (string)$config['db_password'], (string)$config['db_name'],
            (int)($config['db_port'] ?? 3306)
        );
        $this->connection->set_charset('utf8mb4');
    }
    public function prepare(string $sql): DatabaseStatement
    {
        return new DatabaseStatement($this->connection, $this->connection->prepare($sql));
    }
    public function query(string $sql): DatabaseStatement
    {
        $result = $this->connection->query($sql);
        return new DatabaseStatement($this->connection, null, $result);
    }
    public function exec(string $sql): int
    {
        $this->connection->query($sql);
        return $this->connection->affected_rows;
    }
    public function lastInsertId(): string { return (string)$this->connection->insert_id; }
    public function beginTransaction(): bool { return $this->connection->begin_transaction(); }
    public function commit(): bool { return $this->connection->commit(); }
    public function rollBack(): bool { return $this->connection->rollback(); }
}

final class DatabaseStatement
{
    private mysqli $connection;
    private ?mysqli_stmt $statement;
    private mysqli_result|bool|null $result;
    public function __construct(mysqli $connection, ?mysqli_stmt $statement = null, mysqli_result|bool|null $result = null)
    { $this->connection=$connection; $this->statement=$statement; $this->result=$result; }
    public function execute(array $params = []): bool
    {
        if (!$this->statement) return true;
        if ($params) {
            $types=''; $values=[];
            foreach ($params as $value) {
                if (is_int($value)) $types.='i';
                elseif (is_float($value)) $types.='d';
                else $types.='s';
                $values[]=$value;
            }
            $refs=[]; foreach ($values as $key=>$value) $refs[$key]=&$values[$key];
            $this->statement->bind_param($types, ...$refs);
        }
        $this->statement->execute();
        $this->result=$this->statement->get_result();
        return true;
    }
    public function fetch(): array|false
    {
        if ($this->result instanceof mysqli_result) { $row = $this->result->fetch_assoc(); return $row !== null ? $row : false;
        return false;
    }
    }
    public function fetchAll(): array
    {
        if (!$this->result instanceof mysqli_result) return [];
        return $this->result->fetch_all(MYSQLI_ASSOC);
    }
    public function fetchColumn(int $column = 0): mixed
    {
        $row=$this->fetch();
        if ($row===false) return false;
        $values=array_values($row);
        return $values[$column] ?? false;
    }
    public function rowCount(): int
    {
        if ($this->statement) return $this->statement->affected_rows;
        return $this->connection->affected_rows;
    }
}
