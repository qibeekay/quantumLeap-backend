<?php

require_once dirname(__DIR__) . '/../assets/models/Profile.php';
require_once dirname(__DIR__) . '/../assets/controllers/Utils.php';
require_once dirname(__DIR__) . '/../assets/controllers/ProfileEnums.php';

use Enums\CharacterPersonality;
use Enums\WorkEthic;
use Enums\GameSense;
use Enums\SportType;
use Enums\Gender;
use Enums\Level;

class ProfileController
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Method to validate profile data
    public function validateProfileData(array $data): array
    {
        $errors = [];

        // List of common required fields
        $requiredFields = [
            'usertoken',
            'full_name',
            'dob',
            'school_class',
            'image',
            'video',
            'position',
            'contact_info',
            'height',
            'weight',
            'body_type',
            'speed',
            'agility',
            'strength',
            'coordination',
            'stamina',
            'gpa',
            'test_scores',
            'academic_interests',
            'academic_goals',
            'gender',
            'level',
            'character_personality',
            'work_ethic',
            'game_sense',
            'sport_type'
        ];

        // Check each required field
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "$field cannot be empty.";
            }
        }



        // Validate each URL in the video array
        if (isset($data['video'])) {
            if (!is_array($data['video'])) {
                $errors[] = "Video must be an array of URLs.";
            } else {
                foreach ($data['video'] as $index => $videoUrl) {
                    if (!is_string($videoUrl)) {
                        $errors[] = "Video URL at index $index must be a string.";
                    } elseif (!$this->isValidYoutubeUrl($videoUrl)) {
                        $errors[] = "Invalid URL at index $index in the video array.";
                    }
                }
            }
        }


        // Validate enums
        $this->validateEnumFields($data, $errors);

        // Validate sport-specific skills based on sport type
        if (isset($data['sport_type'])) {
            switch ($data['sport_type']) {
                case SportType::Basketball->value:
                    $basketballSkills = ['threept', 'perimeter', 'ballhandling', 'defense', 'rebounding', 'passing'];
                    foreach ($basketballSkills as $skill) {
                        if (empty($data[$skill])) {
                            $errors[] = "$skill cannot be empty for basketball.";
                        }
                    }
                    break;

                case SportType::Football->value:
                    $footballSkills = ['shooting', 'ballcontrol', 'firsttouch', 'dribbling', 'passing', 'defense'];
                    foreach ($footballSkills as $skill) {
                        if (empty($data[$skill])) {
                            $errors[] = "$skill cannot be empty for football.";
                        }
                    }
                    break;

                case SportType::Volleyball->value:
                    $volleyballSkills = ['setting', 'blocking', 'spiking', 'serving', 'passing'];
                    foreach ($volleyballSkills as $skill) {
                        if (empty($data[$skill])) {
                            $errors[] = "$skill cannot be empty for volleyball.";
                        }
                    }
                    break;

                default:
                    $errors[] = "Invalid sport type: " . $data['sport_type'];
            }
        }

        return $errors; // Return the array of error messages
    }

    // Helper method to validate a YouTube URL
    private function isValidYoutubeUrl(string $url): bool
    {
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|.+\?v=)?([\w-]{11})$/';
        return preg_match($pattern, $url) === 1;
    }

    // Method to validate enum fields
    private function validateEnumFields(array $data, array &$errors): void
    {
        // Validate player level
        if (isset($data['level'])) {
            foreach ($data['level'] as $level) {
                if (!Level::tryFrom($level)) {
                    $errors[] = "Invalid player level: $level.";
                }
            }
        }

        // Validate gender
        if (isset($data['gender'])) {
            foreach ($data['gender'] as $gender) {
                if (!Gender::tryFrom($gender)) {
                    $errors[] = "Invalid gender: $gender.";
                }
            }
        }

        // Validate work_ethic
        if (isset($data['work_ethic'])) {
            foreach ($data['work_ethic'] as $ethic) {
                if (!WorkEthic::tryFrom($ethic)) {
                    $errors[] = "Invalid work ethic value: $ethic.";
                }
            }
        }

        // Validate character_personality
        if (isset($data['character_personality'])) {
            foreach ($data['character_personality'] as $personality) {
                if (!CharacterPersonality::tryFrom($personality)) {
                    $errors[] = "Invalid character personality value: $personality.";
                }
            }
        }

        // Validate game_sense
        if (isset($data['game_sense'])) {
            foreach ($data['game_sense'] as $sense) {
                if (!GameSense::tryFrom($sense)) {
                    $errors[] = "Invalid game sense value: $sense.";
                }
            }
        }

        // Validate sport_type
        if (isset($data['sport_type']) && !SportType::tryFrom($data['sport_type'])) {
            $errors[] = "Invalid sport type: " . $data['sport_type'];
        }
    }

    // Method to fetch all profiles
    public function fetchAllProfiles(): array
    {
        $profileModel = new Profile($this->db, []); // Create an instance of Profile
        return $profileModel->getAllProfiles(); // Call the method to get all profiles
    }

    // Method to fetch profile by usertoken
    public function fetchProfileByUserToken(string $usertoken): array
    {
        $profileModel = new Profile($this->db, []);
        $profile = $profileModel->getProfileByUserToken($usertoken);

        if ($profile === null) {
            // Log or return a more informative error message
            return [
                'status' => false,
                'message' => "Profile not found for usertoken: $usertoken. Please verify token validity.",
                'data' => null
            ];
        }

        // Define fields to cast as numbers
        $numericFields = [
            'height',
            'weight',
            'speed',
            'agility',
            'strength',
            'coordination',
            'stamina',
            'gpa',
            'passing',
            'serving',
            'setting',
            'blocking',
            'spiking',
            'threept',
            'perimeter',
            'ballhandling',
            'defense',
            'rebounding',
            'shooting',
            'ballcontrol',
            'firsttouch',
            'dribbling'
        ];

        // Cast numeric fields
        foreach ($numericFields as $field) {
            if (isset($profile[$field])) {
                $profile[$field] = (float) $profile[$field];
            }
        }

        return [
            'status' => true,
            'message' => 'Profile Retrieved!!!',
            'data' => $profile,
        ];
    }

    // Method to fetch profile by gender and level
    public function getProfileByGender($data): array
    {
        $gender = $data['gender'];
        $level = $data['level'];
        $sportType = $data['sport_type'];

        $profileModel = new Profile($this->db, []);
        $profiles = $profileModel->getProfileByGender($gender, $level, $sportType);

        if (empty($profiles)) {
            return [
                'status' => true,
                'message' => "Profiles not available",
                'data' => [],
            ];
        }


        // Define fields to cast as numbers
        $numericFields = [
            'height',
            'weight',
            'speed',
            'agility',
            'strength',
            'coordination',
            'stamina',
            'gpa',
            'passing',
            'serving',
            'setting',
            'blocking',
            'spiking',
            'threept',
            'perimeter',
            'ballhandling',
            'defense',
            'rebounding',
            'shooting',
            'ballcontrol',
            'firsttouch',
            'dribbling'
        ];

        // Cast numeric fields
        // Cast numeric fields
        foreach ($profiles as &$profile) {
            foreach ($numericFields as $field) {
                if (isset($profile[$field])) {
                    $profile[$field] = (float) $profile[$field];
                }
            }
        }

        return [
            'status' => true,
            'message' => 'Profile Retrieved!!!',
            'data' => $profiles,
        ];
    }


    // Method to create a profile
    public function createProfile(array $data)
    {
        // Validate the profile data
        $validationErrors = $this->validateProfileData($data);
        if (!empty($validationErrors)) {
            return [
                'status' => false,
                'errors' => $validationErrors
            ];
        }

        // Extract the token from the data
        $usertoken = $data['usertoken'];

        // Convert validated values to enum instances
        $data['level'] = array_map(fn($level) => Level::from($level), $data['level']);

        $data['gender'] = array_map(fn($gender) => Gender::from($gender), $data['gender']);

        $data['work_ethic'] = array_map(fn($ethic) => WorkEthic::from($ethic), $data['work_ethic']);

        $data['character_personality'] = array_map(fn($personality) => CharacterPersonality::from($personality), $data['character_personality']);

        $data['game_sense'] = array_map(fn($sense) => GameSense::from($sense), $data['game_sense']);

        $data['sport_type'] = is_array($data['sport_type'])
            ? array_map(fn($sport) => SportType::from($sport), $data['sport_type'])
            : SportType::from($data['sport_type']);

        // Proceed with creating the profile if validation passes
        $profile = new Profile($this->db, $data);
        if ($profile->createProfile($usertoken)) {
            return [
                'status' => true,
                'message' => 'Profile created successfully.'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Failed to create profile.'
            ];
        }
    }

    // Method to update a profile
    public function updateProfile(array $data)
    {
        // Validate the profile data
        $validationErrors = $this->validateProfileData($data);
        if (!empty($validationErrors)) {
            return [
                'status' => false,
                'errors' => $validationErrors
            ];
        }

        // Extract the token from the data
        $usertoken = $data['usertoken'];

        // Convert validated values to enum instances
        $data['level'] = array_map(fn($level) => Level::from($level), $data['level']);

        $data['gender'] = array_map(fn($gender) => Gender::from($gender), $data['gender']);

        $data['work_ethic'] = array_map(fn($ethic) => WorkEthic::from($ethic), $data['work_ethic']);

        $data['character_personality'] = array_map(fn($personality) => CharacterPersonality::from($personality), $data['character_personality']);

        $data['game_sense'] = array_map(fn($sense) => GameSense::from($sense), $data['game_sense']);

        $data['sport_type'] = array_map(fn($sport) => SportType::from($sport), $data['sport_type']);

        // Proceed with updating the profile if validation passes
        $profile = new Profile($this->db, $data);
        if ($profile->updateProfile($usertoken)) {
            return [
                'status' => true,
                'message' => 'Profile updated successfully.'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Failed to update profile.'
            ];
        }
    }

    // Method to fetch profile by usertoken
    public function deleteProfileByUserToken(string $usertoken): array
    {
        $profileModel = new Profile($this->db, []);
        $profile = $profileModel->deleteProfile($usertoken);

        if ($profile === null) {
            // Log or return a more informative error message
            return [
                'status' => false,
                'message' => "Profile not found for usertoken: $usertoken. Please verify token validity.",
            ];
        }

        return [
            'status' => true,
            'message' => 'Profile has been deleted',
        ];
    }

}