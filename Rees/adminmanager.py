import mysql.connector

# Where to get connection data from
CONNECTION_DATA_FILE = ".env"

""" Fetch connection data

Returns:
    dict: Dictionnary of connection data 
"""

def fetch_connection_data() -> dict:
    data = ""
    with open(CONNECTION_DATA_FILE, 'r') as file:
        for line in file:
            # Get the line where connection data is present
            if "DATABASE_URL" in line and "#" not in line:
                data = line
                break
        data = data.split("=")[1]
        data = data.split("//")[1]
        
        # username:password
        user_data = data.split("@")[0]
        username, password = user_data.split(":")[0], user_data.split(":")[1]
        
        database_data = data.split("@")[1]
        # host:database
        database_server = database_data.split("/")[0]
        database = database_data.split("/")[1].replace("\n", "")
        host = database_server.split(":")[0]
        port = database_server.split(":")[1]
        
        data = {
            "username": username,
            "password": password,
            "host": host,
            "port": port,
            "database": database
        }
        return data

""" Show all users

Args:
    cursor: database cursor

"""
def show_users(cursor):
    sql = "SELECT id, email, admin FROM user;"
    cursor.execute(sql)
    users = cursor.fetchall()
    print("| USERS |")
    for user in users:
        print(f"-- USER: ID -> {user[0]} | E-mail address -> {user[1]} | Is admin? -> {bool(user[2])} --")

""" Set an admin as an user

Args:
    db_connection: database connection
    cursor: database cursor
    admin_id: admin's ID
"""
def set_admin(db_connection, cursor, admin_id):
    sql = "UPDATE user SET admin = %s WHERE id = %s"
    values = ("1", str(admin_id))
    cursor.execute(sql, values)
    db_connection.commit()
    print("Rows affected:", cursor.rowcount)
 
""" Retrograde an admin to user

Args:
    db_connection: database connection
    cursor: database cursor
    admin_id: admin's ID
"""   
def retrograde_admin(db_connection, cursor, admin_id):
    sql = "UPDATE user SET admin = %s WHERE id = %s"
    values = ("0", str(admin_id))
    cursor.execute(sql, values)
    db_connection.commit()
    print("Rows affected:", cursor.rowcount)

if __name__ == "__main__":
    # Data connection
    CONNECTION_DATA = fetch_connection_data()
    # Initialize connection to the server
    db_connection = mysql.connector.connect(
    host=CONNECTION_DATA["host"],
    port=CONNECTION_DATA["port"],
    user=CONNECTION_DATA["username"],
    password=CONNECTION_DATA["password"],
    database=CONNECTION_DATA["database"]
    )
    # Initialize the cursor
    cursor = db_connection.cursor()
    # Available actions
    ACTIONS = [show_users, set_admin, retrograde_admin]
    
    action = None
    # Main loop
    while True:
        print("-- ACTIONS -- ")
        print("0. Show users")
        print("1. Set a user as an admin")
        print("2. Retrograde an admin to user")
        print("3. Stop the program")
        action = int(input("Choose an action by entering its number: "))
        print()
        if action == 3:
            break
        elif action == 0:
            ACTIONS[0](cursor)
        elif action == 1:
            user_id = int(input("User ID: "))
            ACTIONS[1](db_connection, cursor, user_id)
        elif action == 2:
            user_id = int(input("User ID: "))
            ACTIONS[2](db_connection, cursor, user_id)
        else:
            print("INVALID ACTION. PLEASE ENTER A VALID ACTION.")
        print()