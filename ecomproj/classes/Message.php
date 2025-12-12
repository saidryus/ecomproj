<?php

class Message
{
    // ====== CORE PROPERTIES ======

    //pdo connection used for all queries
    private $conn;

    //table where messages are stored
    private $tablename = 'messages';

    //save db connection when the class is created
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ====== SENDING MESSAGES ======

    //send a new message from one user to another
    public function send($sender_id, $recipient_id, $subject, $body)
    {
        //base insert query for the messages table
        $query = "INSERT INTO " . $this->tablename . "
                  (sender_id, recipient_id, subject, body)
                  VALUES (:sender_id, :recipient_id, :subject, :body)";

        //prepare the statement so we can bind clean values
        $stmt = $this->conn->prepare($query);

        //normalize and cast ids
        $sender_id    = (int)$sender_id;
        $recipient_id = (int)$recipient_id;

        //trim subject and body so we do not store extra spaces
        $subject = trim((string)$subject);
        $body    = trim((string)$body);

        //basic validation: all fields must be present and ids must be > 0
        if ($sender_id <= 0 || $recipient_id <= 0 || $subject === '' || $body === '') {
            return false;
        }

        //bind ids as integers
        $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->bindParam(':recipient_id', $recipient_id, PDO::PARAM_INT);

        //bind subject and body as strings
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':body', $body);

        //execute and return true/false depending on success
        return $stmt->execute();
    }

    // ====== INBOX / SENT QUERIES ======

    //get all messages where the user is the recipient (inbox)
    public function getInbox($user_id)
    {
        //make sure user id is an integer
        $user_id = (int)$user_id;

        //select messages plus the sender username for display
        $query = "SELECT m.*, u.username AS sender_name
                  FROM " . $this->tablename . " m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.recipient_id = :uid
                  ORDER BY m.created_at DESC";

        //prepare the query
        $stmt = $this->conn->prepare($query);
        //bind the current user id
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        //run the query
        $stmt->execute();

        //return the statement so the caller can loop over results
        return $stmt;
    }

    //get all messages where the user is the sender (sent items)
    public function getSent($user_id)
    {
        //make sure user id is an integer
        $user_id = (int)$user_id;

        //select messages plus the recipient username for display
        $query = "SELECT m.*, u.username AS recipient_name
                  FROM " . $this->tablename . " m
                  JOIN users u ON m.recipient_id = u.id
                  WHERE m.sender_id = :uid
                  ORDER BY m.created_at DESC";

        //prepare the query
        $stmt = $this->conn->prepare($query);
        //bind the current user id
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        //run the query
        $stmt->execute();

        //return the statement so the caller can loop over results
        return $stmt;
    }

    // ====== SINGLE MESSAGE ACCESS ======

    //load a single message if the user is allowed to see it
    public function getByIdForUser($id, $user_id)
    {
        //force both ids to integers
        $id      = (int)$id;
        $user_id = (int)$user_id;

        //select the message plus sender and recipient usernames
        $query = "SELECT m.*, 
                         s.username AS sender_name, 
                         r.username AS recipient_name
                  FROM " . $this->tablename . " m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.recipient_id = r.id
                  WHERE m.id = :id
                    AND (m.sender_id = :uid OR m.recipient_id = :uid)
                  LIMIT 1";

        //prepare the query
        $stmt = $this->conn->prepare($query);
        //bind message id and user id
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        //run the query
        $stmt->execute();

        //fetch a single row as an associative array
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //return the row if found, otherwise null
        return $row ? $row : null;
    }

    //mark a message as read, only if the user is the recipient
    public function markAsRead($id, $user_id)
    {
        //cast ids to int to avoid weird values
        $id      = (int)$id;
        $user_id = (int)$user_id;

        //update the is_read flag for this message
        $query = "UPDATE " . $this->tablename . " 
                  SET is_read = 1 
                  WHERE id = :id AND recipient_id = :uid";

        //prepare the query
        $stmt = $this->conn->prepare($query);
        //bind both ids
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);

        //run the update and return true/false
        return $stmt->execute();
    }

    // ====== UNREAD COUNT ======

    //count how many unread messages a user has
    public function countUnread($user_id)
    {
        //cast user id to int
        $user_id = (int)$user_id;

        //count all unread messages for this user
        $query = "SELECT COUNT(*) AS total
                  FROM " . $this->tablename . "
                  WHERE recipient_id = :uid
                    AND is_read = 0";

        //prepare the query
        $stmt = $this->conn->prepare($query);
        //bind the user id
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        //run the query
        $stmt->execute();

        //get the count row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //return the integer total or 0 if nothing came back
        return $row ? (int)$row['total'] : 0;
    }
}
