<?php
namespace db\install {
    function schema() {
        tables();
        triggers();
        views();
    }
    function tables() {
        $sql =<<<'SQL'
        DROP TABLE IF EXISTS teams;
        CREATE TABLE teams (
            name            TEXT        NOT NULL,
            abbr            TEXT        NOT NULL,
            ranking         INTEGER,
            CONSTRAINT pk_teams         PRIMARY KEY (name)
        );

        DROP TABLE IF EXISTS games;
        CREATE TABLE games (
            id               INTEGER     NOT NULL,
            home             TEXT        NOT NULL,
            home_goals       INTEGER,
            home_goals_total INTEGER,
            home_percent     REAL,
            road             TEXT        NOT NULL,
            road_goals       INTEGER,
            road_goals_total INTEGER,
            road_percent     REAL,
            draw             INTERGER    NOT NULL CONSTRAINT df_draw   DEFAULT 1,
            draw_percent     REAL,
            score            TEXT,
            points           REAL        NOT NULL CONSTRAINT df_points DEFAULT 0,
            time             TEXT        NOT NULL,
            CONSTRAINT pk_games         PRIMARY KEY (id),
            CONSTRAINT fk_teams_home    FOREIGN KEY (home) REFERENCES teams (name),
            CONSTRAINT fk_teams_road    FOREIGN KEY (road) REFERENCES teams (name)
        );

        DROP TABLE IF EXISTS scorers;
        CREATE TABLE scorers (
            name            TEXT        NOT NULL,
            team            TEXT        NOT NULL,
            goals           INTEGER     NOT NULL,
            CONSTRAINT pk_scorers       PRIMARY KEY (name),
            CONSTRAINT fk_teams         FOREIGN KEY (team) REFERENCES teams (name)
        );

        DROP TABLE IF EXISTS users;
        CREATE TABLE users (
            username        TEXT        NOT NULL,
            password        TEXT,
            claim           TEXT,
            email           TEXT,
            winner          TEXT,
            winner_points   INTEGER     NOT NULL CONSTRAINT df_winner_points DEFAULT 0,
            second          TEXT,
            second_points   INTEGER     NOT NULL CONSTRAINT df_second_points DEFAULT 0,
            third           TEXT,
            third_points    INTEGER     NOT NULL CONSTRAINT df_third_points  DEFAULT 0,
            scorer          TEXT,
            scorer_points   INTEGER     NOT NULL CONSTRAINT df_scorer_points DEFAULT 0,
            active          INTEGER     NOT NULL CONSTRAINT df_active DEFAULT 1,
            admin           INTEGER     NOT NULL CONSTRAINT df_admin  DEFAULT 0,
            paid            INTEGER     NOT NULL CONSTRAINT df_pain   DEFAULT 0,
            visited_time    TEXT,
            visited_page    TEXT,
            CONSTRAINT pk_users         PRIMARY KEY (username),
            CONSTRAINT fk_teams_winner  FOREIGN KEY (winner) REFERENCES teams (name),
            CONSTRAINT fk_teams_second  FOREIGN KEY (second) REFERENCES teams (name),
            CONSTRAINT fk_teams_third   FOREIGN KEY (third)  REFERENCES teams (name)
        );

        DROP TABLE IF EXISTS scorermap;
        CREATE TABLE scorermap (
            scorer          TEXT NOT NULL,
            betted          TEXT NOT NULL,
            CONSTRAINT pk_scorermap PRIMARY KEY (scorer, betted),
            CONSTRAINT fk_scorers FOREIGN KEY (scorer) REFERENCES scorer (name),
            CONSTRAINT fk_users   FOREIGN KEY (betted) REFERENCES users (scorer)
        );

        DROP INDEX IF EXISTS idx_users_email;
        CREATE UNIQUE INDEX idx_users_email ON users (email);

        DROP INDEX IF EXISTS idx_users_claim;
        CREATE UNIQUE INDEX idx_users_claim ON users (claim);

        DROP TABLE IF EXISTS remember;
        CREATE TABLE remember (
            user            TEXT        NOT NULL,
            key             TEXT        NOT NULL,
            expire          TEXT        NOT NULL,
            CONSTRAINT pk_remember      PRIMARY KEY (key, user),
            CONSTRAINT fk_users         FOREIGN KEY (user) REFERENCES users (username)
        );

        DROP TABLE IF EXISTS gamebets;
        CREATE TABLE gamebets (
            game            INTEGER     NOT NULL,
            user            TEXT        NOT NULL,
            score           TEXT        NOT NULL,
            points          REAL        NOT NULL CONSTRAINT df_points DEFAULT 0,
            CONSTRAINT pk_gamebets      PRIMARY KEY (game, user),
            CONSTRAINT fk_games         FOREIGN KEY (game) REFERENCES games (id),
            CONSTRAINT fk_users         FOREIGN KEY (user) REFERENCES users (username)
        );

        DROP TABLE IF EXISTS news;
        CREATE TABLE news (
            id              INTEGER     NOT NULL,
            time            TEXT        NOT NULL,
            user            TEXT        NOT NULL,
            title           TEXT        NOT NULL,
            content         TEXT        NOT NULL,
            level           INTEGER     NOT NULL,
            slug            TEXT        NOT NULL,
            CONSTRAINT pk_news          PRIMARY KEY (id),
            CONSTRAINT fk_users         FOREIGN KEY (user) REFERENCES users (username)
        );

        DROP TABLE IF EXISTS chat;
        CREATE TABLE chat (
            id              INTEGER     NOT NULL,
            time            TEXT        NOT NULL,
            user            TEXT        NOT NULL,
            message         TEXT        NOT NULL,
            CONSTRAINT pk_chat          PRIMARY KEY (id),
            CONSTRAINT fk_users         FOREIGN KEY (user) REFERENCES users (username)
        );
SQL;
        $db = \db\connect();
        $db->exec($sql);
    }
    function triggers() {
        $sql =<<< 'SQL'
        DROP TRIGGER IF EXISTS trigger_games;
        CREATE TRIGGER trigger_games AFTER UPDATE OF points ON games
        BEGIN
            UPDATE gamebets SET points = 0          WHERE game = new.id;
            UPDATE gamebets SET points = new.points WHERE game = new.id AND score IS NOT NULL AND score = new.score;
        END;

        DROP TRIGGER IF EXISTS trigger_teams;
        CREATE TRIGGER trigger_teams AFTER UPDATE OF ranking ON teams
        BEGIN
            UPDATE users SET winner_points = 0 WHERE winner IS NOT NULL AND winner = new.name;
            UPDATE users SET winner_points = 4 WHERE winner IS NOT NULL AND winner = new.name AND new.ranking = 1;
            UPDATE users SET winner_points = 1 WHERE winner IS NOT NULL AND winner = new.name AND (new.ranking = 2 OR new.ranking = 3);

            UPDATE users SET second_points = 0 WHERE second IS NOT NULL AND second = new.name;
            UPDATE users SET second_points = 3 WHERE second IS NOT NULL AND second = new.name AND new.ranking = 2;
            UPDATE users SET second_points = 1 WHERE second IS NOT NULL AND second = new.name AND (new.ranking = 1 OR new.ranking = 3);

            UPDATE users SET third_points = 0  WHERE third  IS NOT NULL AND third = new.name;
            UPDATE users SET third_points = 2  WHERE third  IS NOT NULL AND third = new.name  AND new.ranking = 3;
            UPDATE users SET third_points = 1  WHERE third  IS NOT NULL AND third = new.name  AND (new.ranking = 1 OR new.ranking = 2);
        END;

        DROP TRIGGER IF EXISTS trigger_gamebets_insert;
        CREATE TRIGGER trigger_gamebets_insert AFTER INSERT ON gamebets
        BEGIN
            UPDATE games SET home_percent = (
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game AND score = '1')
                /
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game) * 100.0
            )
            WHERE id = new.game;

            UPDATE games SET draw_percent = (
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game AND score = 'X')
                /
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game) * 100.0
            )
            WHERE id = new.game;

            UPDATE games SET road_percent = (
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game AND score = '2')
                /
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game) * 100.0
            )
            WHERE id = new.game;
        END;

        DROP TRIGGER IF EXISTS trigger_gamebets_update;
        CREATE TRIGGER trigger_gamebets_update AFTER UPDATE OF score ON gamebets
        BEGIN
            UPDATE games SET home_percent = (
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game AND score = '1')
                /
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game) * 100.0
            )
            WHERE id = new.game;

            UPDATE games SET draw_percent = (
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game AND score = 'X')
                /
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game) * 100.0
            )
            WHERE id = new.game;

            UPDATE games SET road_percent = (
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game AND score = '2')
                /
                (SELECT COUNT(game) + 0.0 FROM gamebets WHERE game IS NOT NULL AND game = new.game) * 100.0
            )
            WHERE id = new.game;
        END;

        DROP TRIGGER IF EXISTS trigger_scorermap_insert;
        CREATE TRIGGER trigger_scorermap_insert AFTER INSERT ON scorermap
        BEGIN
            UPDATE users SET scorer_points = 0 WHERE scorer = new.betted;
            UPDATE users SET scorer_points = 3
            WHERE
                scorer = new.betted
                AND
                scorer IN (
                SELECT
                    sm.betted
                FROM
                    scorermap sm
                INNER JOIN
                    scorers s
                ON
                    sm.scorer = s.name
                WHERE
                    s.goals = (SELECT MAX(goals) FROM scorers)
            );
        END;

        DROP TRIGGER IF EXISTS trigger_scorermap_update;
        CREATE TRIGGER trigger_scorermap_update AFTER UPDATE ON scorermap
        BEGIN
            UPDATE users SET scorer_points = 0 WHERE scorer = old.betted;
            UPDATE users SET scorer_points = 3
            WHERE
                scorer = new.betted
                AND
                scorer IN (
                SELECT
                    sm.betted
                FROM
                    scorermap sm
                INNER JOIN
                    scorers s
                ON
                    sm.scorer = s.name
                WHERE
                    s.goals = (SELECT MAX(goals) FROM scorers)
            );
        END;

        DROP TRIGGER IF EXISTS trigger_scorers_insert;
        CREATE TRIGGER trigger_scorers_insert AFTER INSERT ON scorers
        BEGIN
            UPDATE users SET scorer_points = 0;
            UPDATE users SET scorer_points = 3
            WHERE scorer IN (
                SELECT
                    sm.betted
                FROM
                    scorermap sm
                INNER JOIN
                    scorers s
                ON
                    sm.scorer = s.name
                WHERE
                    s.goals = (SELECT MAX(goals) FROM scorers)
            );
        END;

        DROP TRIGGER IF EXISTS trigger_scorers_update;
        CREATE TRIGGER trigger_scorers_update AFTER UPDATE OF goals ON scorers
        BEGIN
            UPDATE users SET scorer_points = 0;
            UPDATE users SET scorer_points = 3
            WHERE scorer IN (
                SELECT
                    sm.betted
                FROM
                    scorermap sm
                INNER JOIN
                    scorers s
                ON
                    sm.scorer = s.name
                WHERE
                    s.goals = (SELECT MAX(goals) FROM scorers)
            );
        END;
SQL;
        $db = \db\connect();
        $db->exec($sql);
    }
    function views() {
        $sql =<<<'SQL'
        DROP VIEW IF EXISTS view_games;
        CREATE VIEW view_games AS
        SELECT
            g.id AS id,
            g.time AS time,
            g.draw AS draw,
            g.draw_percent AS draw_percent,
            g.score AS score,
            g.home AS home,
            g.home_goals AS home_goals,
            g.home_goals_total AS home_goals_total,
            g.home_percent AS home_percent,
            h.abbr AS home_abbr,
            g.road AS road,
            g.road_goals AS road_goals,
            g.road_goals_total AS road_goals_total,
            g.road_percent AS road_percent,
            r.abbr AS road_abbr,
            g.points AS points
        FROM
            games AS g
        INNER JOIN
            teams AS h
        ON
            g.home = h.name
        INNER JOIN
            teams AS r
        ON
            g.road = r.name;

        DROP VIEW IF EXISTS view_users;
        CREATE VIEW view_users AS
        SELECT
            u.username        AS username,
            u.password        AS password,
            u.claim           AS claim,
            u.email           AS email,
            u.winner          AS winner,
            t.abbr            AS winner_abbr,
            t.ranking         AS winner_ranking,
            u.winner_points   AS winner_points,
            u.second          AS second,
            t2.abbr           AS second_abbr,
            t2.ranking        AS second_ranking,
            u.second_points   AS second_points,
            u.third           AS third,
            t3.abbr           AS third_abbr,
            t3.ranking        AS third_ranking,
            u.third_points    AS third_points,
            s.name            AS scorer,
            u.scorer          AS scorer_betted,
            u.scorer_points   AS scorer_points,
            s.goals           AS scorer_goals,
            u.active          AS active,
            u.admin           AS admin,
            u.paid            AS paid,
            u.visited_time    AS visited_time,
            u.visited_page    AS visited_page
        FROM
            users u
        LEFT OUTER JOIN
            teams t
        ON
            u.winner = t.name
        LEFT OUTER JOIN
            teams t2
        ON
            u.second = t2.name
        LEFT OUTER JOIN
            teams t3
        ON
            u.third = t3.name
        LEFT OUTER JOIN
            scorermap sm
        ON
            sm.betted = u.scorer
        LEFT OUTER JOIN
            scorers s
        ON
            sm.scorer = s.name
        LEFT OUTER JOIN
            teams t4
        ON
            s.team = t4.name;
SQL;
        $db = \db\connect();
        $db->exec($sql);
    }
}