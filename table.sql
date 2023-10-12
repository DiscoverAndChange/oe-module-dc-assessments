#IfTable HostSite
-- Cleanup old tables that are not namespaced.
DROP TABLE IF EXISTS `view_current_assessments`;
DROP VIEW IF EXISTS `view_current_assessments`;

DROP TABLE IF EXISTS `ReportVersion`;
DROP TABLE IF EXISTS `ReportPermission`;
DROP TABLE IF EXISTS `Report`;
DROP TABLE IF EXISTS `AnnouncementSystemUserRead`;
DROP TABLE IF EXISTS `Announcement`;
DROP TABLE IF EXISTS `AssessmentGroupAssessmentBlob`;
DROP TABLE IF EXISTS `AssessmentResultBlob`;
DROP TABLE IF EXISTS `AssessmentGroupPermission`;
DROP TABLE IF EXISTS `AssignmentItem`;
DROP TABLE IF EXISTS `Assignment`;
DROP TABLE IF EXISTS `AssessmentBlob`;
DROP TABLE IF EXISTS `AssessmentGroup`;
DROP TABLE IF EXISTS `LibraryAssetBlobTag`;
DROP TABLE IF EXISTS `LibraryAssetResultBlob`;
DROP TABLE IF EXISTS `LibraryAssetBlob`;
DROP TABLE IF EXISTS `Tag`;
DROP TABLE IF EXISTS `SystemVersion`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `Role`;
DROP TABLE IF EXISTS `HostSite`;
#EndIf

-- -----------------------------------------------------
-- Table `dac_Role`
-- -----------------------------------------------------
#IfNotTable dac_Role
CREATE TABLE IF NOT EXISTS `dac_Role` (
                                      `id` INT NOT NULL AUTO_INCREMENT,
                                      `name` VARCHAR(45) NOT NULL,
                                      `description` VARCHAR(255) NOT NULL,
                                      PRIMARY KEY (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `Announcement`
-- -----------------------------------------------------
#IfNotTable dac_Announcement
CREATE TABLE IF NOT EXISTS `dac_Announcement` (
                                              `id` INT NOT NULL AUTO_INCREMENT,
                                              `message` TEXT NOT NULL,
                                              `link_url` VARCHAR(512) NOT NULL,
                                              `link_title` VARCHAR(60) NOT NULL,
                                              `expires_date` DATETIME(6) NOT NULL,
                                              `creation_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                              `last_update_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                              `created_by` BIGINT(20) NOT NULL,
                                              `last_updated_by` BIGINT(20) NOT NULL,
                                              PRIMARY KEY (`id`),
                                              INDEX `FK_9bb4a0d71a5662b66f916be8e27` (`created_by` ASC),
                                              INDEX `FK_0038c5a542ba583368535b38bfd` (`last_updated_by` ASC),
                                              CONSTRAINT `FK_0038c5a542ba583368535b38bfd`
                                                  FOREIGN KEY (`last_updated_by`)
                                                      REFERENCES `users` (`id`),
                                              CONSTRAINT `FK_9bb4a0d71a5662b66f916be8e27`
                                                  FOREIGN KEY (`created_by`)
                                                      REFERENCES `users` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `AnnouncementSystemUserRead`
-- -----------------------------------------------------
#IfNotTable dac_AnnouncementSystemUserRead
CREATE TABLE IF NOT EXISTS `dac_AnnouncementSystemUserRead` (
                                                            `id` INT NOT NULL AUTO_INCREMENT,
                                                            `date_viewed` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                            `systemuser_id` bigint(20) NOT NULL,
                                                            `announcement_id` INT NOT NULL,
                                                            PRIMARY KEY (`id`),
                                                            INDEX `FK_5d5b992f9fe2a4be98d32ad34d0` (`systemuser_id` ASC),
                                                            INDEX `FK_03f6ea00c02986670dddaf53096` (`announcement_id` ASC),
                                                            CONSTRAINT `FK_03f6ea00c02986670dddaf53096`
                                                                FOREIGN KEY (`announcement_id`)
                                                                    REFERENCES `dac_Announcement` (`id`),
                                                            CONSTRAINT `FK_5d5b992f9fe2a4be98d32ad34d0`
                                                                FOREIGN KEY (`systemuser_id`)
                                                                    REFERENCES `users` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_AssessmentBlob`
-- -----------------------------------------------------
#IfNotTable dac_AssessmentBlob
CREATE TABLE IF NOT EXISTS `dac_AssessmentBlob` (
                                                `id` INT NOT NULL AUTO_INCREMENT,
                                                `uid` VARCHAR(32) NOT NULL,
                                                `data` LONGTEXT NULL DEFAULT NULL,
                                                `date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                `name` VARCHAR(255) NULL DEFAULT NULL,
                                                `company_id` INT NULL DEFAULT NULL,
                                                `description` TEXT NULL DEFAULT NULL,
                                                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                                                PRIMARY KEY (`id`),
                                                INDEX `assessmentblob_idx_uid` (`uid` ASC),
                                                INDEX `FK_91d91ea76d9fff05f176dcbc79f` (`company_id` ASC),
                                                CONSTRAINT `FK_91d91ea76d9fff05f176dcbc79f`
                                                    FOREIGN KEY (`company_id`)
                                                        REFERENCES `facility` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_AssessmentGroup`
-- -----------------------------------------------------
#EndIf dac_AssessmentGroup
CREATE TABLE IF NOT EXISTS `dac_AssessmentGroup` (
                                                 `id` INT NOT NULL AUTO_INCREMENT,
                                                 `company_id` INT NULL DEFAULT NULL,
                                                 `name` VARCHAR(100) NOT NULL,
                                                 `date_updated` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                 `date_created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                 PRIMARY KEY (`id`),
                                                 UNIQUE INDEX `idx_uq_ag` (`company_id` ASC, `name` ASC),
                                                 CONSTRAINT `FK_bca0be9f2a6add5cbe66c61c440`
                                                     FOREIGN KEY (`company_id`)
                                                         REFERENCES `facility` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_AssessmentGroupAssessmentBlob`
-- -----------------------------------------------------
#IfNotTable dac_AssessmentGroupAssessmentBlob
CREATE TABLE IF NOT EXISTS `dac_AssessmentGroupAssessmentBlob` (
                                                               `assessmentblob_id` INT NOT NULL,
                                                               `assessmentgroup_id` INT NOT NULL,
                                                               `display_order` TINYINT NOT NULL DEFAULT '1',
                                                               PRIMARY KEY (`assessmentblob_id`, `assessmentgroup_id`),
                                                               INDEX `idx_agroup_ablob` (`assessmentgroup_id` ASC, `assessmentblob_id` ASC),
                                                               CONSTRAINT `FK_6ebbb6eb68a9919764843d2fb55`
                                                                   FOREIGN KEY (`assessmentgroup_id`)
                                                                       REFERENCES `dac_AssessmentGroup` (`id`),
                                                               CONSTRAINT `FK_c006524e820d27de0baf7dbf4f7`
                                                                   FOREIGN KEY (`assessmentblob_id`)
                                                                       REFERENCES `dac_AssessmentBlob` (`id`))
    ENGINE = InnoDB;
#EndIf

-- -----------------------------------------------------
-- Table `dac_AssessmentGroupPermission`
-- -----------------------------------------------------
#IfNotTable dac_AssessmentGroupPermission
CREATE TABLE IF NOT EXISTS `dac_AssessmentGroupPermission` (
                                                           `id` INT NOT NULL AUTO_INCREMENT,
                                                           `show` TINYINT(1) NOT NULL DEFAULT '1',
                                                           `company_id` INT NULL DEFAULT NULL,
                                                           `assessmentgroup_id` INT NOT NULL,
                                                           PRIMARY KEY (`id`, `assessmentgroup_id`),
                                                           INDEX `FK_cbd6fb3b502f5b58eec80181ed3` (`company_id` ASC),
                                                           INDEX `FK_6108a2e71951e0257befe8d2e20` (`assessmentgroup_id` ASC),
                                                           CONSTRAINT `FK_6108a2e71951e0257befe8d2e20`
                                                               FOREIGN KEY (`assessmentgroup_id`)
                                                                   REFERENCES `dac_AssessmentGroup` (`id`),
                                                           CONSTRAINT `FK_cbd6fb3b502f5b58eec80181ed3`
                                                               FOREIGN KEY (`company_id`)
                                                                   REFERENCES `facility` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_AssessmentResultBlob`
-- -----------------------------------------------------
#IfNotTable dac_AssessmentResultBlob
CREATE TABLE IF NOT EXISTS `dac_AssessmentResultBlob` (
                                                      `id` VARCHAR(255) NOT NULL,
                                                      `assessment_id` INT NULL DEFAULT NULL,
                                                      `client_id` BIGINT NULL DEFAULT NULL,
                                                      `client_custom_field_1` VARCHAR(255) NULL DEFAULT NULL,
                                                      `data` LONGTEXT NULL DEFAULT NULL,
                                                      `date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                      PRIMARY KEY (`id`),
                                                      INDEX `IDX_136CCD2DDD3DD5F1` (`assessment_id` ASC),
                                                      INDEX `FK_fa8df7a7bfb166f3ead4de1e7c9` (`client_id` ASC),
                                                      CONSTRAINT `FK_bd9b984bf0b459f65b35a3470f5`
                                                          FOREIGN KEY (`assessment_id`)
                                                              REFERENCES `dac_AssessmentBlob` (`id`),
                                                      CONSTRAINT `FK_fa8df7a7bfb166f3ead4de1e7c9`
                                                          FOREIGN KEY (`client_id`)
                                                              REFERENCES `patient_data` (`id`))
    ENGINE = InnoDB;
#EndIf

-- -----------------------------------------------------
-- Table `dac_Assignment`
-- -----------------------------------------------------
-- TODO: list_options really should have a surrogate pk key to aid in foreign key references and performance...
-- Right now there is no foreign key checks against list_options.option_id, so if someone deletes that template and
-- there are assignments that reference it, the assignment will be orphaned.
#IfNotTable dac_Assignment
CREATE TABLE IF NOT EXISTS `dac_Assignment` (
                                            `id` INT NOT NULL AUTO_INCREMENT,
                                            `uuid` binary(16) NULL DEFAULT NULL,
                                            `client_id` BIGINT NULL DEFAULT NULL,
                                            `assessmentgroup_id` INT NULL DEFAULT NULL,
                                            `assessmentblob_id` INT NULL DEFAULT NULL,
                                            `template_profile_list_option_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'fk to list_options.option_id where list=Document_Template_Profiles',
                                            `appointment_id` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT 'fk to openemr_postcalendar_events.eid',
                                            `audit_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'fk to onsite_portal_activity.id',
                                            `date_updated` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                            `date_assigned` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                            `date_completed` DATETIME(6) NULL DEFAULT NULL,
                                            `date_created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                            `created_by` BIGINT(20) NULL DEFAULT NULL,
                                            `last_updated_by` BIGINT(20) NULL DEFAULT NULL,
                                            PRIMARY KEY (`id`),
                                            INDEX `FK_30684b6d7f0968a3c257e5de686` (`client_id` ASC),
                                            INDEX `FK_ab4f0bf1756de2825e7b4378863` (`assessmentgroup_id` ASC),
                                            INDEX `FK_3611f5bebbc6763cb14b0206503` (`assessmentblob_id` ASC),
                                            INDEX `FK_asgnmt_template_profile_list_option_id` (`template_profile_list_option_id` ASC),
                                            INDEX `FK_asgnmt_appointment_id` (`appointment_id` ASC),
                                            INDEX `FK_asgnmt_audit_id` (`audit_id` ASC),
                                            INDEX `FK_ai_last_updated_by` (`last_updated_by` ASC),
                                            INDEX `FK_ai_created_by` (`created_by` ASC),
                                            CONSTRAINT UNIQUE KEY `uuid_unique` (`uuid`),
                                            CONSTRAINT `FK_30684b6d7f0968a3c257e5de686`
                                                FOREIGN KEY (`client_id`)
                                                    REFERENCES `patient_data` (`pid`),
                                            CONSTRAINT `FK_3611f5bebbc6763cb14b0206503`
                                                FOREIGN KEY (`assessmentblob_id`)
                                                    REFERENCES `dac_AssessmentBlob` (`id`),
                                            CONSTRAINT `FK_ab4f0bf1756de2825e7b4378863`
                                                FOREIGN KEY (`assessmentgroup_id`)
                                                    REFERENCES `dac_AssessmentGroup` (`id`),
                                            CONSTRAINT `FK_asgnmt_appointment_id`
                                                FOREIGN KEY(`appointment_id`)
                                                    REFERENCES `openemr_postcalendar_events`(`pc_eid`)
                                                    ON DELETE SET NULL ON UPDATE CASCADE,
                                            CONSTRAINT `FK_asgnmt_audit_id`
                                                FOREIGN KEY (`audit_id`)
                                                    REFERENCES `onsite_portal_activity` (`id`)
                                                    ON DELETE SET NULL ON UPDATE CASCADE,
                                            CONSTRAINT `FK_asgnmt_last_updated_by`
                                                FOREIGN KEY (`last_updated_by`)
                                                    REFERENCES `users` (`id`),
                                            CONSTRAINT `FK_asgnmt_created_by`
                                                FOREIGN KEY (`created_by`)
                                                    REFERENCES `users` (`id`)
                                        )
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_LibraryAssetBlob`
-- -----------------------------------------------------
#IfNotTable dac_LibraryAssetBlob
CREATE TABLE IF NOT EXISTS `dac_LibraryAssetBlob` (
                                                  `id` INT NOT NULL AUTO_INCREMENT,
                                                  `title` VARCHAR(64) NOT NULL,
                                                  `description` VARCHAR(255) NOT NULL,
                                                  `type` VARCHAR(32) NOT NULL,
                                                  `view_count` INT NOT NULL,
                                                  `use_count` INT NOT NULL,
                                                  `original_creator` VARCHAR(255) NOT NULL,
                                                  `creator_link` VARCHAR(255) NULL,
                                                  `content` TEXT NOT NULL,
                                                  `journal` TEXT NULL DEFAULT NULL,
                                                  `creation_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                  `last_update_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                  `created_by` BIGINT(20) NOT NULL,
                                                  `last_updated_by` BIGINT(20) NOT NULL,
                                                  PRIMARY KEY (`id`),
                                                  INDEX `FK_eab662c262c371affe843e0afbb` (`created_by` ASC),
                                                  INDEX `FK_8385894b046cb1be1844a59a99d` (`last_updated_by` ASC),
                                                  CONSTRAINT `FK_8385894b046cb1be1844a59a99d`
                                                      FOREIGN KEY (`last_updated_by`)
                                                          REFERENCES `users` (`id`),
                                                  CONSTRAINT `FK_eab662c262c371affe843e0afbb`
                                                      FOREIGN KEY (`created_by`)
                                                          REFERENCES `users` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_LibraryAssetResultBlob`
-- -----------------------------------------------------
#IfNotTable dac_LibraryAssetResultBlob
CREATE TABLE IF NOT EXISTS `dac_LibraryAssetResultBlob` (
                                                        `id` VARCHAR(255) NOT NULL,
                                                        `answers` MEDIUMBLOB NULL DEFAULT NULL,
                                                        `journal_entry` MEDIUMBLOB NULL DEFAULT NULL,
                                                        `creation_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                        `asset_id` INT NULL DEFAULT NULL,
                                                        `client_id` BIGINT NULL DEFAULT NULL,
                                                        PRIMARY KEY (`id`),
                                                        INDEX `FK_c93dee116748da7e63dd15f0e22` (`asset_id` ASC),
                                                        INDEX `FK_274694214f6ccc3aa40fd3267bd` (`client_id` ASC),
                                                        CONSTRAINT `FK_274694214f6ccc3aa40fd3267bd`
                                                            FOREIGN KEY (`client_id`)
                                                                REFERENCES `patient_data` (`id`),
                                                        CONSTRAINT `FK_c93dee116748da7e63dd15f0e22`
                                                            FOREIGN KEY (`asset_id`)
                                                                REFERENCES `dac_LibraryAssetBlob` (`id`))
    ENGINE = InnoDB;
#EndIf

-- -----------------------------------------------------
-- Table `dac_AssignmentItem`
-- -----------------------------------------------------
#IfNotTable dac_AssignmentItem
CREATE TABLE IF NOT EXISTS `dac_AssignmentItem` (
                                                `id` INT NOT NULL AUTO_INCREMENT,
                                                `uuid` binary(16) NULL DEFAULT NULL,
                                                `date_updated` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                `date_assigned` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                `date_completed` DATETIME(6) NULL DEFAULT NULL,
                                                `date_created` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                `assignment_id` INT NOT NULL,
                                                `assessmentblob_id` INT NULL DEFAULT NULL,
                                                `assessmentresultblob_id` VARCHAR(255) NULL DEFAULT NULL,
                                                `asset_id` INT NULL DEFAULT NULL,
                                                `assetresultblob_id` VARCHAR(255) NULL DEFAULT NULL,
                                                `document_template_id` BIGINT(21) unsigned NULL DEFAULT NULL,
                                                `document_id` INT(11) NULL DEFAULT NULL,
                                                `audit_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'fk to onsite_portal_activity.id',
                                                `created_by` BIGINT(20) NULL DEFAULT NULL,
                                                `last_updated_by` BIGINT(20) NULL DEFAULT NULL,
                                                PRIMARY KEY (`id`),
                                                INDEX `FK_a935c413d1275cbb67f4f33b267` (`assignment_id` ASC),
                                                INDEX `FK_65f8a2d33f0bf480d6eeee14658` (`assessmentblob_id` ASC),
                                                INDEX `FK_ed940532fded912967125ac8679` (`asset_id` ASC),
                                                INDEX `FK_b27983280af94057568c20fc5b1` (`assetresultblob_id` ASC),
                                                INDEX `FK_ai_document_template_id` (`document_template_id` ASC),
                                                INDEX `FK_ai_document_id` (`document_id` ASC),
                                                INDEX `FK_ai_audit_id` (`audit_id` ASC),
                                                INDEX `FK_ai_last_updated_by` (`last_updated_by` ASC),
                                                INDEX `FK_ai_created_by` (`created_by` ASC),
                                                CONSTRAINT UNIQUE KEY `uuid_unique` (`uuid`),
                                                CONSTRAINT `FK_65f8a2d33f0bf480d6eeee14658`
                                                    FOREIGN KEY (`assessmentblob_id`)
                                                        REFERENCES `dac_AssessmentBlob` (`id`),
                                                CONSTRAINT `FK_a935c413d1275cbb67f4f33b267`
                                                    FOREIGN KEY (`assignment_id`)
                                                        REFERENCES `dac_Assignment` (`id`),
                                                CONSTRAINT `FK_b27983280af94057568c20fc5b1`
                                                    FOREIGN KEY (`assetresultblob_id`)
                                                        REFERENCES `dac_LibraryAssetResultBlob` (`id`),
                                                CONSTRAINT `FK_ed940532fded912967125ac8679`
                                                    FOREIGN KEY (`asset_id`)
                                                        REFERENCES `dac_LibraryAssetBlob` (`id`),
                                                CONSTRAINT `FK_ai_onsite_document_id`
                                                    FOREIGN KEY (`document_template_id`)
                                                        REFERENCES `document_templates` (`id`),
                                                CONSTRAINT `FK_ai_document_id`
                                                    FOREIGN KEY (`document_id`)
                                                        REFERENCES `documents` (`id`),
                                                CONSTRAINT `FK_ai_audit_id`
                                                    FOREIGN KEY (`audit_id`)
                                                        REFERENCES `onsite_portal_activity` (`id`)
                                                        ON DELETE SET NULL ON UPDATE CASCADE,
                                                CONSTRAINT `FK_ai_last_updated_by`
                                                    FOREIGN KEY (`last_updated_by`)
                                                        REFERENCES `users` (`id`),
                                                CONSTRAINT `FK_ai_created_by`
                                                    FOREIGN KEY (`created_by`)
                                                        REFERENCES `users` (`id`)
                                            )
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_Tag`
-- -----------------------------------------------------
#IfNotTable dac_Tag
CREATE TABLE IF NOT EXISTS `dac_Tag` (
                                     `id` INT NOT NULL AUTO_INCREMENT,
                                     `tag` VARCHAR(45) NOT NULL,
                                     PRIMARY KEY (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_LibraryAssetBlobTag`
-- -----------------------------------------------------
#IfNotTable dac_LibraryAssetBlobTag
CREATE TABLE IF NOT EXISTS `dac_LibraryAssetBlobTag` (
                                                     `library_asset_blob_id` INT NOT NULL,
                                                     `tag_id` INT NOT NULL,
                                                     PRIMARY KEY (`library_asset_blob_id`, `tag_id`),
                                                     INDEX `FK_812bca87b3f6c7b09f6e246ee17` (`tag_id` ASC),
                                                     CONSTRAINT `FK_812bca87b3f6c7b09f6e246ee17`
                                                         FOREIGN KEY (`tag_id`)
                                                             REFERENCES `dac_Tag` (`id`)
                                                             ON DELETE CASCADE,
                                                     CONSTRAINT `FK_832d483a4ca7bdb4a30d26174dd`
                                                         FOREIGN KEY (`library_asset_blob_id`)
                                                             REFERENCES `dac_LibraryAssetBlob` (`id`)
                                                             ON DELETE CASCADE)
    ENGINE = InnoDB;
#EndIf

-- -----------------------------------------------------
-- Table `dac_Report`
-- -----------------------------------------------------
#IfNotTable dac_Report
CREATE TABLE IF NOT EXISTS `dac_Report` (
                                        `id` VARCHAR(255) NOT NULL,
                                        `name` VARCHAR(255) NOT NULL,
                                        `created_by` BIGINT(20) NOT NULL,
                                        `creation_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                        `last_updated_by` BIGINT(20) NOT NULL,
                                        `last_update_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                        `assessmentgroup_id` INT NULL DEFAULT NULL,
                                        `assessment_uid` VARCHAR(32) NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        UNIQUE INDEX `id_UNIQUE` (`id` ASC),
                                        INDEX `FK_d7b11f18173da5b233f675d82ce` (`created_by` ASC),
                                        INDEX `FK_ba0a40db4e487a0592d81d3262b` (`last_updated_by` ASC),
                                        INDEX `FK_d3f756889a6839f89ff436386af` (`assessmentgroup_id` ASC),
                                        CONSTRAINT `FK_ba0a40db4e487a0592d81d3262b`
                                            FOREIGN KEY (`last_updated_by`)
                                                REFERENCES `users` (`id`),
                                        CONSTRAINT `FK_d3f756889a6839f89ff436386af`
                                            FOREIGN KEY (`assessmentgroup_id`)
                                                REFERENCES `dac_AssessmentGroup` (`id`),
                                        CONSTRAINT `FK_d7b11f18173da5b233f675d82ce`
                                            FOREIGN KEY (`created_by`)
                                                REFERENCES `users` (`id`))
    ENGINE = InnoDB;
#EndIf

-- -----------------------------------------------------
-- Table `dac_ReportPermission`
-- -----------------------------------------------------
#IfNotTable dac_ReportPermission
CREATE TABLE IF NOT EXISTS `dac_ReportPermission` (
                                                  `id` INT NOT NULL AUTO_INCREMENT,
                                                  `report_id` VARCHAR(255) NOT NULL,
                                                  `company_id` INT NULL DEFAULT NULL,
                                                  `show` TINYINT(1) NOT NULL DEFAULT '1',
                                                  `created_by` BIGINT(20) NOT NULL,
                                                  `creation_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                  `last_updated_by` BIGINT(20) NOT NULL,
                                                  `last_update_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                                  PRIMARY KEY (`id`),
                                                  INDEX `FK_9ba100015f4e261f3dd9393b27e` (`report_id` ASC),
                                                  INDEX `FK_c5860d5449f87126f8bc076318d` (`company_id` ASC),
                                                  INDEX `FK_f16196ce0d1b51649bf16195c70` (`created_by` ASC),
                                                  INDEX `FK_5d2dc4334b2fdb880b4c6e4016c` (`last_updated_by` ASC),
                                                  CONSTRAINT `FK_5d2dc4334b2fdb880b4c6e4016c`
                                                      FOREIGN KEY (`last_updated_by`)
                                                          REFERENCES `users` (`id`),
                                                  CONSTRAINT `FK_9ba100015f4e261f3dd9393b27e`
                                                      FOREIGN KEY (`report_id`)
                                                          REFERENCES `dac_Report` (`id`),
                                                  CONSTRAINT `FK_c5860d5449f87126f8bc076318d`
                                                      FOREIGN KEY (`company_id`)
                                                          REFERENCES `facility` (`id`),
                                                  CONSTRAINT `FK_f16196ce0d1b51649bf16195c70`
                                                      FOREIGN KEY (`created_by`)
                                                          REFERENCES `users` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_ReportVersion`
-- -----------------------------------------------------
#IfNotTable dac_ReportVersion
CREATE TABLE IF NOT EXISTS `dac_ReportVersion` (
                                               `id` INT NOT NULL AUTO_INCREMENT,
                                               `report_id` VARCHAR(255) NOT NULL,
                                               `version` INT NOT NULL DEFAULT '1',
                                               `data` TEXT NOT NULL,
                                               `created_by` BIGINT(20) NOT NULL,
                                               `creation_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                               `last_updated_by` BIGINT(20) NOT NULL,
                                               `last_update_date` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                               PRIMARY KEY (`id`),
                                               INDEX `idx_version_lookup` (`report_id` ASC, `version` ASC),
                                               INDEX `FK_44e4ae2f2798d623369c16ded57` (`created_by` ASC),
                                               INDEX `FK_e8cf30e920f8b38f66f25548de0` (`last_updated_by` ASC),
                                               CONSTRAINT `FK_44e4ae2f2798d623369c16ded57`
                                                   FOREIGN KEY (`created_by`)
                                                       REFERENCES `users` (`id`),
                                               CONSTRAINT `FK_82ebc6a7ea51d6dc8d41a62ab52`
                                                   FOREIGN KEY (`report_id`)
                                                       REFERENCES `dac_Report` (`id`),
                                               CONSTRAINT `FK_e8cf30e920f8b38f66f25548de0`
                                                   FOREIGN KEY (`last_updated_by`)
                                                       REFERENCES `users` (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Table `dac_SystemVersion`
-- -----------------------------------------------------
#IfNotTable dac_SystemVersion
CREATE TABLE IF NOT EXISTS `dac_SystemVersion` (
                                               `id` INT NOT NULL,
                                               `version` VARCHAR(100) NOT NULL,
                                               `date_updated` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                                               PRIMARY KEY (`id`))
    ENGINE = InnoDB;
#EndIf

-- -----------------------------------------------------
-- Table `dac_migrations`
-- -----------------------------------------------------
#IfNotTable dac_migrations
CREATE TABLE IF NOT EXISTS `dac_migrations` (
                                            `id` INT(11) NOT NULL AUTO_INCREMENT,
                                            `timestamp` BIGINT(20) NOT NULL,
                                            `name` VARCHAR(255) NOT NULL,
                                            PRIMARY KEY (`id`))
    ENGINE = InnoDB
    AUTO_INCREMENT = 1;
#EndIf

-- -----------------------------------------------------
-- Placeholder table for view `dac_view_current_assessments`
-- -----------------------------------------------------
#IfNotTable dac_view_current_assessments
CREATE TABLE IF NOT EXISTS `dac_view_current_assessments` (`id` INT, `uid` INT, `name` INT, `date` INT, `data` INT);

-- -----------------------------------------------------
-- View `view_current_assessments`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `dac_view_current_assessments`;
DROP VIEW IF EXISTS `dac_view_current_assessments` ;
CREATE OR REPLACE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `dac_view_current_assessments` AS SELECT `ab1`.`id` AS `id`, `ab1`.`uid` AS `uid`, `ab1`.`name` AS `name`, `ab1`.`date` AS `date`, `ab1`.`data` AS `data` FROM (`dac_AssessmentBlob` `ab1` JOIN (SELECT MAX(`ab2`.`id`) AS `id`, `ab2`.`uid` AS `uid` FROM `dac_AssessmentBlob` `ab2` GROUP BY `ab2`.`uid`) `latest_ab` ON ((`latest_ab`.`id` = `ab1`.`id`)));
#EndIf

-- -----------------------------------------------------
-- Data for table `dac_Role`
-- -----------------------------------------------------
#IfNotRow dac_Role id 1
START TRANSACTION;
INSERT INTO `dac_Role` (`id`, `name`, `description`) VALUES (1, 'superuser', 'The sitewide system administrator');
INSERT INTO `dac_Role` (`id`, `name`, `description`) VALUES (2, 'owner', 'The primary account owner of the company');
INSERT INTO `dac_Role` (`id`, `name`, `description`) VALUES (3, 'admin', 'A company administrator');
INSERT INTO `dac_Role` (`id`, `name`, `description`) VALUES (4, 'registered', 'A regular user in the system');
COMMIT;
#EndIf

-- -----------------------------------------------------
-- Data for table `dac_Tag`
-- -----------------------------------------------------
#IfNotRow dac_Tag tag Depression
START TRANSACTION;
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (1, 'Affairs/infidelity');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (2, 'Anger management');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (3, 'Anxiety');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (4, 'Dating');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (5, 'Depression');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (6, 'Dieting and weight loss');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (7, 'Divorce');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (8, 'Domestic violence');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (9, 'Drug addiction');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (10, 'Eating disorders');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (11, 'Exercising');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (12, 'Family');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (13, 'Gambling addiction');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (14, 'Grief and loss');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (15, 'Happiness');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (16, 'Marriage');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (17, 'Nutrition');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (18, 'OCD');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (19, 'PTSD');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (20, 'Parenting');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (21, 'Pornography addiction');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (22, 'Relationship advice');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (23, 'Relationships');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (24, 'Self-esteem');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (25, 'Sex addiction');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (26, 'Sexual intimacy');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (27, 'Social phobia');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (28, 'Step families');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (29, 'Stress');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (30, 'Substance abuse');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (31, 'Social Skills');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (32, 'Panic Attacks');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (33, 'Phobias');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (34, 'Self-confidence');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (35, 'General');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (36, 'ADHD');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (37, 'Sexual Betrayal Trauma');
INSERT INTO `dac_Tag` (`id`, `tag`) VALUES (38, 'Self-Compassion');
COMMIT;
#EndIf

-- -----------------------------------------------------
-- Data for table `SystemVersion`
-- -----------------------------------------------------
#IfNotRow dac_SystemVersion version 111
START TRANSACTION;
INSERT INTO `dac_SystemVersion` (`id`, `version`, `date_updated`) VALUES (1, '111', NOW());
COMMIT;
#EndIf

#IfMissingColumn dac_AssignmentItem questionnaire_id
ALTER TABLE `dac_AssignmentItem` ADD `questionnaire_id` BIGINT(21) UNSIGNED NULL , ADD `questionnaire_response_id` VARCHAR(255) NULL;
ALTER TABLE `dac_AssignmentItem` ADD CONSTRAINT `FK_assignmentitem_questionnaire_id` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaire_repository`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `dac_AssignmentItem` ADD CONSTRAINT `FK_assignmentitem_questionnaire_response_id` FOREIGN KEY (`questionnaire_response_id`) REFERENCES `questionnaire_response`(`response_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
#EndIf

-- Spec at https://build.fhir.org/questionnaire-definitions.html#Questionnaire.url says questionnaire urls
--   `SHOULD be globally unique
--    and SHOULD be a literal address at which an authoritative instance of this questionnaire is (or will be) published`
-- We need to update our urls to be the address of the fhir url if we have it.
UPDATE `questionnaire_repository` qr CROSS JOIN ( select `gl_value` FROM `globals` WHERE `gl_name`='site_addr_oath' AND `gl_value` IS NOT NULL AND `gl_value` != '' ) gbl SET qr.`source_url` = CONCAT(gbl.`gl_value`, '/apis/default/', qr.`source_url`) WHERE qr.`source_url` LIKE 'fhir/%';

#IfNotRow2D globals gl_name oauth_ehr_launch_authorization_flow_skip gl_value 1
START TRANSACTION;
UPDATE `globals` SET `gl_value` = 1 WHERE `gl_name` = 'oauth_ehr_launch_authorization_flow_skip';
COMMIT;
#EndIf

#IfMissingColumn dac_AssessmentBlob uuid
ALTER TABLE dac_AssessmentBlob ADD `uuid` binary(16) NULL DEFAULT NULL;
#EndIf

#IfMissingColumn dac_LibraryAssetBlob uuid
ALTER TABLE dac_LibraryAssetBlob ADD `uuid` binary(16) NULL DEFAULT NULL;
#EndIf
