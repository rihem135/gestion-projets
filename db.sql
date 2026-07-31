-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'membre') NOT NULL DEFAULT 'membre',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_project_id INT DEFAULT NULL
);

-- Table des projets
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Ajout de la contrainte pour last_project_id (doit être après la création de projects)
ALTER TABLE users
ADD CONSTRAINT fk_last_project_id
FOREIGN KEY (last_project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- Table des tâches
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'Not Started',
    deadline DATE,
    is_public BOOLEAN DEFAULT 0,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);


-- Insertion de l'admin (nom: admin / adresse: admin@gmail.com / mot de passe: admin)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `last_project_id`) VALUES ('1', 'admin', 'admin@gmail.com', '$2y$10$q71SuQvKRDl.wiJqzfZlqOYmevgIoU1J/67PZSYfNg08u8AU2Hmje', 'admin', current_timestamp(), NULL);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    file VARCHAR(255),
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);
