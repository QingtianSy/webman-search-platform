import threading

import pymysql
from pymysql.err import OperationalError


db_lock = threading.Lock()


def create_table_if_not_exists(self):
    table = self.gui.db_config["db_table"]
    if self.db_connection is None:
        raise ConnectionError("database connection unavailable")

    with self.db_connection.cursor() as cursor:
        sql = f"""
        CREATE TABLE IF NOT EXISTS {table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id VARCHAR(50),
            course_name VARCHAR(255),
            quetype VARCHAR(50),
            question TEXT,
            options TEXT,
            ans TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        """
        cursor.execute(sql)
        self.db_connection.commit()


def insert_data(self, questions_and_options_and_answers, course_id="", course_name=""):
    table = self.gui.db_config["db_table"]
    with db_lock:
        self.db_connection = check_and_reconnect_database(self)
        if self.db_connection is None:
            raise ConnectionError("database connection unavailable")

        create_table_if_not_exists(self)

        with self.db_connection.cursor() as cursor:
            base_query = (
                f"INSERT INTO {table} "
                "(course_id, course_name, quetype, question, options, ans) "
                "VALUES (%s, %s, %s, %s, %s, %s)"
            )
            batch_data = [
                (
                    course_id,
                    course_name,
                    item["type"],
                    item["question"],
                    item["options"],
                    item["answer"],
                )
                for item in questions_and_options_and_answers
            ]
            cursor.executemany(base_query, batch_data)
            self.db_connection.commit()


def check_and_reconnect_database(self):
    try:
        if self.db_connection is None:
            self.db_connection = get_db_connection(self)
            return self.db_connection
        self.db_connection.ping(reconnect=True)
        return self.db_connection
    except (OperationalError, AttributeError):
        self.db_connection = get_db_connection(self)
        return self.db_connection


def get_db_connection(self):
    try:
        self.db_connection = pymysql.connect(
            host=self.gui.db_config["db_address"],
            port=int(self.gui.db_config["db_port"]),
            user=self.gui.db_config["db_user"],
            password=self.gui.db_config["db_password"],
            db=self.gui.db_config["db_name"],
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor,
        )
        return self.db_connection
    except Exception:
        return None
