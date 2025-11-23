-- Cria database de testes se não existir
CREATE DATABASE IF NOT EXISTS travel_system_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Garante que o usuário root tem acesso ao database de testes
GRANT ALL PRIVILEGES ON travel_system_test.* TO 'root'@'%';
FLUSH PRIVILEGES;

