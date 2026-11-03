-- Estrutura original do banco ideau_gv (SHOW CREATE TABLE), capturada em 2026-11-03.
-- Todas as tabelas estavam vazias. Usada pela migration de baseline para recriar o banco do zero.
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `Usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_completo` int(11) NOT NULL,
  `data_nascimento` date NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `email` int(200) NOT NULL,
  `senha` int(200) NOT NULL,
  `tipo_usuario` enum('Professor','Aluno','Biblioteca/Coordenação','') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(250) NOT NULL,
  `cnpj` varchar(14) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Estagio` (
  `id` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_termino` date NOT NULL,
  `status` enum('Finalizado','Não autorizado','Documentos Incompletos','Documentos Entregues') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_empresa` (`id_empresa`) USING BTREE,
  KEY `idx_id_aluno` (`id_aluno`) USING BTREE,
  CONSTRAINT `fk_empresa_estagiada` FOREIGN KEY (`id_empresa`) REFERENCES `Empresa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_estagiante` FOREIGN KEY (`id_aluno`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `TCC` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_aluno` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `aprovado` enum('Não aprovado','Aprovado','Esperando resposta','') DEFAULT NULL,
  `id_professor_orientador` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `TCCLog` (
  `id` int(11) NOT NULL,
  `id_tcc` int(11) NOT NULL,
  `data_enviado` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `arquivo` int(11) NOT NULL,
  `titulo` varchar(2000) NOT NULL,
  `comentario` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_usuario` (`id_usuario`),
  KEY `fk_log_tcc` (`id_tcc`),
  CONSTRAINT `fk_log_tcc` FOREIGN KEY (`id_tcc`) REFERENCES `TCC` (`id`),
  CONSTRAINT `fk_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Solicitacao` (
  `id` int(11) NOT NULL,
  `tipo_de_solicitacao` enum('Solicitação de documento','Solicitação de troca de professor','Solicitação de orientador','Solicitação de defesa') NOT NULL,
  `id_aluno` int(11) NOT NULL,
  `id_resposta` int(11) DEFAULT NULL,
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Esperando resposta','Não aprovado','Aprovado','') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_solicitante` (`id_aluno`),
  KEY `fk_resposta_diretoria` (`id_resposta`),
  CONSTRAINT `fk_resposta_diretoria` FOREIGN KEY (`id_resposta`) REFERENCES `RespostaSolicitacao` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_solicitante` FOREIGN KEY (`id_aluno`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `RespostaSolicitacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitacao` int(11) NOT NULL,
  `data` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Defesa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_aluno` int(11) NOT NULL,
  `id_tcc` int(11) NOT NULL,
  `criado_em` int(11) NOT NULL,
  `agendado_para` int(11) NOT NULL,
  `id_solicitacao` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tcc` (`id_tcc`),
  KEY `fk_aluno` (`id_aluno`),
  KEY `fk_solicitacao` (`id_solicitacao`),
  CONSTRAINT `fk_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_solicitacao` FOREIGN KEY (`id_solicitacao`) REFERENCES `Solicitacao` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tcc` FOREIGN KEY (`id_tcc`) REFERENCES `TCC` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Banca` (
  `id_defesa` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_defesa` (`id_defesa`),
  CONSTRAINT `Banca_ibfk_1` FOREIGN KEY (`id`) REFERENCES `Ata` (`id_banca`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_defesa` FOREIGN KEY (`id_defesa`) REFERENCES `Defesa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ParticipanteBanca` (
  `id_professor` int(11) NOT NULL,
  `id_banca` int(11) NOT NULL,
  `funcao` enum('Avaliador','Presidente') NOT NULL,
  PRIMARY KEY (`id_banca`,`id_professor`),
  KEY `fk_professor` (`id_professor`),
  CONSTRAINT `fk_banca` FOREIGN KEY (`id_banca`) REFERENCES `Banca` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_professor` FOREIGN KEY (`id_professor`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Avaliacao` (
  `id` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL COMMENT 'Chave estrangeira para a tabela Professor.',
  `id_banca` int(11) NOT NULL,
  `nota` decimal(10,0) NOT NULL,
  `emitida_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_professor_avaliador` (`id_professor`),
  KEY `fk_participante_banca` (`id_banca`),
  CONSTRAINT `fk_participante_banca` FOREIGN KEY (`id_banca`) REFERENCES `Banca` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_professor_avaliador` FOREIGN KEY (`id_professor`) REFERENCES `Usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `Ata` (
  `id` int(11) NOT NULL,
  `id_banca` int(11) NOT NULL,
  `nota_1` int(11) NOT NULL,
  `nota_2` int(11) NOT NULL,
  `nota_3` int(11) NOT NULL,
  `media_final` decimal(10,0) NOT NULL,
  `aprovado` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_nota_1` (`nota_1`),
  KEY `fk_nota_2` (`nota_2`),
  KEY `fk_nota_3` (`nota_3`),
  KEY `fk_ata_banca` (`id_banca`),
  CONSTRAINT `fk_ata_banca` FOREIGN KEY (`id_banca`) REFERENCES `Banca` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nota_1` FOREIGN KEY (`nota_1`) REFERENCES `Avaliacao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nota_2` FOREIGN KEY (`nota_2`) REFERENCES `Avaliacao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_nota_3` FOREIGN KEY (`nota_3`) REFERENCES `Avaliacao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
