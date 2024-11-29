<?php

require_once dirname(__DIR__) . '/../assets/controllers/ProfileEnums.php';
require_once dirname(__DIR__) . '/../assets/controllers/Utils.php';


use Enums\CharacterPersonality;
use Enums\WorkEthic;
use Enums\GameSense;
use Enums\SportType;
use Enums\Gender;
use Enums\Level;

class Profile
{

    private $connection;

    private string $table = 'userprofile';

    // profile properties
    private array $data;

    // constructor to accept database connection and profile data
    public function __construct($db, array $data = [])
    {
        $this->connection = $db;

        if (!empty($data)) {
            $this->data = [
                'full_name' => htmlspecialchars(strip_tags($data['full_name'] ?? '')),
                'dob' => htmlspecialchars(strip_tags($data['dob'] ?? '')),
                'school_class' => htmlspecialchars(strip_tags($data['school_class'] ?? '')),
                'image' => htmlspecialchars(strip_tags($data['image'] ?? '')),
                'video' => isset($data['video']) ? json_encode(array_map('htmlspecialchars', $data['video'])) : json_encode([]),
                'position' => htmlspecialchars(strip_tags($data['position'] ?? '')),
                'contact_info' => htmlspecialchars(strip_tags($data['contact_info'] ?? '')),
                'height' => htmlspecialchars(strip_tags($data['height'] ?? '')),
                'weight' => htmlspecialchars(strip_tags($data['weight'] ?? '')),
                'body_type' => htmlspecialchars(strip_tags($data['body_type'] ?? '')),
                'speed' => htmlspecialchars(strip_tags($data['speed'] ?? '')),
                'agility' => htmlspecialchars(strip_tags($data['agility'] ?? '')),
                'strength' => htmlspecialchars(strip_tags($data['strength'] ?? '')),
                'coordination' => htmlspecialchars(strip_tags($data['coordination'] ?? '')),
                'stamina' => htmlspecialchars(strip_tags($data['stamina'] ?? '')),
                'gpa' => htmlspecialchars(strip_tags($data['gpa'] ?? '')),
                'test_scores' => htmlspecialchars(strip_tags($data['test_scores'] ?? '')),
                'academic_interests' => htmlspecialchars(strip_tags($data['academic_interests'] ?? '')),
                'academic_goals' => htmlspecialchars(strip_tags($data['academic_goals'] ?? '')),
                'gender' => $this->validateAndPrepareEnum($data['gender'], Gender::class),
                'level' => $this->validateAndPrepareEnum($data['level'], Level::class),
                'character_personality' => $this->validateAndPrepareEnum($data['character_personality'], CharacterPersonality::class),
                'work_ethic' => $this->validateAndPrepareEnum($data['work_ethic'], WorkEthic::class),
                'game_sense' => $this->validateAndPrepareEnum($data['game_sense'], GameSense::class),
                'sport_type' => $data['sport_type'] instanceof SportType ? $data['sport_type']->value : htmlspecialchars(strip_tags($data['sport_type'] ?? '')),
                'passing' => htmlspecialchars(strip_tags($data['passing'] ?? '')),
                'serving' => htmlspecialchars(strip_tags($data['serving'] ?? '')),
                'setting' => htmlspecialchars(strip_tags($data['setting'] ?? '')),
                'blocking' => htmlspecialchars(strip_tags($data['blocking'] ?? '')),
                'spiking' => htmlspecialchars(strip_tags($data['spiking'] ?? '')),
                'threept' => htmlspecialchars(strip_tags($data['threept'] ?? '')),
                'perimeter' => htmlspecialchars(strip_tags($data['perimeter'] ?? '')),
                'ballhandling' => htmlspecialchars(strip_tags($data['ballhandling'] ?? '')),
                'defense' => htmlspecialchars(strip_tags($data['defense'] ?? '')),
                'rebounding' => htmlspecialchars(strip_tags($data['rebounding'] ?? '')),
                'shooting' => htmlspecialchars(strip_tags($data['shooting'] ?? '')),
                'ballcontrol' => htmlspecialchars(strip_tags($data['ballcontrol'] ?? '')),
                'firsttouch' => htmlspecialchars(strip_tags($data['firsttouch'] ?? '')),
                'dribbling' => htmlspecialchars(strip_tags($data['dribbling'] ?? '')),
            ];
        } else {
            $this->data = [];
        }
    }

    private function validateAndPrepareEnum(array $values, string $enumClass): string
    {
        $validValues = [];
        foreach ($values as $value) {
            if ($value instanceof $enumClass) {
                // If $value is already an enum instance
                $validValues[] = htmlspecialchars($value->value);
            } elseif (is_string($value) || is_int($value)) {
                // Check if the enum case exists
                $enumInstance = $enumClass::tryFrom($value);
                if ($enumInstance !== null) {
                    $validValues[] = htmlspecialchars($enumInstance->value);
                } else {
                    error_log("Undefined enum case: $value in class $enumClass");
                }
            } else {
                error_log("Invalid value type: " . gettype($value) . " in class $enumClass");
            }
        }
        return implode(',', $validValues);
    }

    // Method to verify user token and retrieve user_id
    private function getUserIdFromToken($usertoken): ?int
    {
        $query = "SELECT id FROM users WHERE usertoken = :usertoken";
        $statement = $this->connection->query($query, ['usertoken' => $usertoken]);

        return $statement->fetchColumn(); // This returns the user ID or null
    }

    // Check if the user already has a profile
    private function profileExists($user_id): bool
    {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id";
        $statement = $this->connection->query($query, ['user_id' => $user_id]);

        return $statement->fetchColumn() > 0; // Return true if a profile exists
    }

    // Method to get all profiles
    public function getAllProfiles(): array
    {
        $query = "SELECT * FROM {$this->table}";
        $statement = $this->connection->query($query);

        return $statement->getAll();
    }

    // Method to get a profile by usertoken
    public function getProfileByUserToken($usertoken): ?array
    {
        // Get user_id from usertoken
        $user_id = $this->getUserIdFromToken($usertoken);
        if (!$user_id) {
            return null; // Return null if user_id is not found
        }

        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $statement = $this->connection->query($query, ['user_id' => $user_id]);

        $profile = $statement->find();

        if ($profile) {
            // Convert the binary data of 'image' to Base64 if it exists
            if (isset($profile['image'])) {
                $profile['image'] = 'data:image/jpeg;base64,' . base64_encode($profile['image']);
            }

            // Convert the binary data of 'test_scores' to Base64 if it exists
            if (isset($profile['test_scores'])) {
                // Adjust MIME type based on file format (assuming PDF for this example)
                $profile['test_scores'] = 'data:application/pdf;base64,' . base64_encode($profile['test_scores']);
            }

            // Decode the video JSON field to an array
            if (isset($profile['video'])) {
                $profile['video'] = json_decode($profile['video'], true);
            }
        }

        return $profile;
    }

    // Method to fetch profile by gender and level
    public function getProfileByGender(string $gender, string $level, string $sportType): ?array
    {
        // Define the query to filter by gender and level
        $query = "SELECT p.*, u.usertoken 
        FROM {$this->table} p
        JOIN users u ON p.user_id = u.id
        WHERE p.gender = :gender AND p.level = :level AND p.sport_type = :sport_type
    ";

        // Prepare and execute the query with the parameters
        $statement = $this->connection->query($query, [
            'gender' => $gender,
            'level' => $level,
            'sport_type' => $sportType
        ]);

        $profile = $statement->getAll(); // Fetch all profiles matching the criteria

        // Check if any profiles were found
        if (empty($profile)) {
            return null; // Return null if no profiles found
        }

        // Process the profiles (if any)
        foreach ($profile as &$p) {
            // Convert the binary data of 'image' to Base64 if it exists
            if (isset($p['image'])) {
                $p['image'] = 'data:image/jpeg;base64,' . base64_encode($p['image']);
            }

            // Convert the binary data of 'test_scores' to Base64 if it exists
            if (isset($p['test_scores'])) {
                $p['test_scores'] = 'data:application/pdf;base64,' . base64_encode($p['test_scores']);
            }

            // Decode the video JSON field to an array
            if (isset($p['video'])) {
                $p['video'] = json_decode($p['video'], true);
            }
        }

        return $profile; // Return the processed profiles
    }


    public function createProfile($usertoken)
    {

        // Verify and get user_id from the token
        $user_id = $this->getUserIdFromToken($usertoken);
        if (!$user_id) {
            return Utils::returnData(false, 'Access denied: invalid or missing token.', null, true);
            // return false;
        }

        // Check if profile already exists for the user
        if ($this->profileExists($user_id)) {
            return Utils::returnData(false, 'Profile already exists for this user.', null, true);
        }

        // Update user_id in data array
        $this->data['user_id'] = $user_id;

        // Decode base64 image data
        if (!empty($this->data['image'])) {
            // Check if the image is a valid base64 string
            if (preg_match('/^data:image\/(\w+);base64,/', $this->data['image'], $type)) {
                $this->data['image'] = substr($this->data['image'], strpos($this->data['image'], ',') + 1);
                $this->data['image'] = base64_decode($this->data['image']);
                if ($this->data['image'] === false) {
                    return Utils::returnData(false, 'Base64 decode failed.', null, true);
                }
            } else {
                return Utils::returnData(false, 'Invalid image format.', null, true);
            }
        }

        if (!empty($this->data['test_scores'])) {
            if (preg_match('/^data:application\/(pdf|vnd.openxmlformats-officedocument.wordprocessingml.document|msword);base64,/', $this->data['test_scores'])) {
                $this->data['test_scores'] = substr($this->data['test_scores'], strpos($this->data['test_scores'], ',') + 1);
                $decodedData = base64_decode($this->data['test_scores'], true);
                if ($decodedData === false) {
                    return Utils::returnData(false, 'Base64 decode failed for test scores.', null, true);
                }
                $this->data['test_scores'] = $decodedData; // Store the decoded data for insertion
            } else {
                return Utils::returnData(false, 'Invalid test scores format.', null, true);
            }
        }


        // Convert video array to JSON if it exists
        if (isset($this->data['video']) && is_array($this->data['video'])) {
            $this->data['video'] = json_encode($this->data['video']);
        }

        // Set fields based on the sport type
        switch ($this->data['sport_type']) {
            case SportType::Basketball->value:
                // Set football and volleyball fields to NULL
                $this->data['shooting'] = null;
                $this->data['ballcontrol'] = null;
                $this->data['firsttouch'] = null;
                $this->data['dribbling'] = null;
                $this->data['serving'] = null;
                $this->data['setting'] = null;
                $this->data['blocking'] = null;
                $this->data['spiking'] = null;
                break;

            case SportType::Football->value:
                // Set basketball and volleyball fields to NULL
                $this->data['threept'] = null;
                $this->data['perimeter'] = null;
                $this->data['ballhandling'] = null;
                $this->data['rebounding'] = null;
                $this->data['serving'] = null;
                $this->data['setting'] = null;
                $this->data['blocking'] = null;
                $this->data['spiking'] = null;
                break;

            case SportType::Volleyball->value:
                // Set basketball and football fields to NULL
                $this->data['threept'] = null;
                $this->data['perimeter'] = null;
                $this->data['ballhandling'] = null;
                $this->data['defense'] = null;
                $this->data['rebounding'] = null;
                $this->data['shooting'] = null;
                $this->data['ballcontrol'] = null;
                $this->data['firsttouch'] = null;
                $this->data['dribbling'] = null;
                break;

            default:
                return Utils::returnData(false, 'Invalid sport type.', null, true);
        }

        // create query
        // Create the SQL query with only the relevant fields
        $query = "INSERT INTO {$this->table} (user_id, full_name, dob, school_class, image, video, position, contact_info, height, weight, body_type, speed, agility, strength, coordination, stamina, gpa, test_scores, academic_interests, academic_goals, gender, level, character_personality, work_ethic, game_sense, passing, serving, shooting, setting, blocking, spiking, ballcontrol, firsttouch, dribbling, threept, perimeter, ballhandling, defense, rebounding, sport_type) 
        VALUES (:user_id, :full_name, :dob, :school_class, :image, :video, :position, :contact_info, :height, :weight, :body_type, :speed, :agility, :strength, :coordination, :stamina, :gpa, :test_scores, :academic_interests, :academic_goals, :gender, :level, :character_personality, :work_ethic, :game_sense, :passing, :serving, :shooting, :setting, :blocking, :spiking, :ballcontrol, :firsttouch, :dribbling, :threept, :perimeter, :ballhandling, :defense, :rebounding, :sport_type)";

        $statement = $this->connection->query($query, $this->data);

        if ($statement) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $this->connection->statement->errorInfo()[2]); // Print the actual error message

        return false;
    }

    // update users profile
    public function updateProfile($usertoken)
    {

        // Verify and get user_id from the token
        $user_id = $this->getUserIdFromToken($usertoken);
        if (!$user_id) {
            return Utils::returnData(false, 'Access denied: invalid or missing token.', null, true);
            // return false;
        }

        // Check if profile already exists for the user
        if (!$this->profileExists($user_id)) {
            return Utils::returnData(false, 'No profile to edit.', null, true);
        }

        // Update user_id in data array
        $this->data['user_id'] = $user_id;

        // create query
        $query = "UPDATE {$this->table} SET 
            full_name = :full_name,
            dob = :dob,
            school_class = :school_class,
            image = :image,
            video = :video,
            position = :position,
            contact_info = :contact_info,
            height = :height,
            weight = :weight,
            body_type = :body_type,
            speed = :speed,
            agility = :agility,
            strength = :strength,
            coordination = :coordination,
            stamina = :stamina,
            gpa = :gpa,
            test_scores = :test_scores,
            academic_interests = :academic_interests,
            academic_goals = :academic_goals,
            gender =:gender,
            level = :level,
            character_personality = :character_personality,
            work_ethic = :work_ethic,
            game_sense = :game_sense,
            athletic_skill = :athletic_skill
        WHERE user_id = :user_id";
        ;

        $statement = $this->connection->query($query, $this->data);

        if ($statement) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $this->connection->statement->errorInfo()[2]); // Print the actual error message

        return false;
    }

    public function deleteProfile($usertoken)
    {

        // Verify and get user_id from the token
        $user_id = $this->getUserIdFromToken($usertoken);
        if (!$user_id) {
            return Utils::returnData(false, 'Access denied: invalid or missing token.', null, true);
            // return false;
        }

        // Check if profile already exists for the user
        if (!$this->profileExists($user_id)) {
            return Utils::returnData(false, 'No profile to edit.', null, true);
        }

        // Update user_id in data array
        $this->data['user_id'] = $user_id;

        // Prepare the delete query
        $query = "DELETE FROM {$this->table} WHERE user_id = :user_id";

        // Execute the delete query
        $statement = $this->connection->query($query, ['user_id' => $user_id]);

        if ($statement) {
            return true;
        }

        // Print error if something goes wrong
        printf("Error: %s.\n", $this->connection->statement->errorInfo()[2]); // Print the actual error message

        return false;
    }

}


