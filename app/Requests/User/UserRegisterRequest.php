<?php

namespace app\Requests\User;

use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class UserRegisterRequest extends Request
{
    private array $validatedData = [];
    private array $errors = [];

    public function validation(): array
    {
        $data = $this->getJsonData();

        $this->validateFirstName($data['first_name'] ?? null);
        $this->validateSecondName($data['second_name'] ?? null);
        $this->validateBirthdate($data['birthdate'] ?? null);
        $this->validateBiography($data['biography'] ?? null);
        $this->validateCity($data['city'] ?? null);
        $this->validatePassword($data['password'] ?? null);

        if (!empty($this->errors)) {
            ErrorResponse::createJson('Invalid parameters', HttpCode::BAD_REQUEST, $this->errors);
        }

        return $this->validatedData;
    }

    private function validateFirstName(?string $firstName): void
    {
        if (empty($firstName)) {
            $this->errors['first_name'] = 'First name is required';
            return;
        }

        if (!is_string($firstName)) {
            $this->errors['first_name'] = 'First name must be a string';
            return;
        }

        if (strlen($firstName) < 2) {
            $this->errors['first_name'] = 'First name must be at least 2 characters long';
            return;
        }

        if (strlen($firstName) > 50) {
            $this->errors['first_name'] = 'First name must not exceed 50 characters';
            return;
        }

        if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $firstName)) {
            $this->errors['first_name'] = 'First name contains invalid characters';
            return;
        }

        $this->validatedData['first_name'] = trim($firstName);
    }

    private function validateSecondName(?string $secondName): void
    {
        if (empty($secondName)) {
            $this->errors['second_name'] = 'Second name is required';
            return;
        }

        if (!is_string($secondName)) {
            $this->errors['second_name'] = 'Second name must be a string';
            return;
        }

        if (strlen($secondName) < 2) {
            $this->errors['second_name'] = 'Second name must be at least 2 characters long';
            return;
        }

        if (strlen($secondName) > 50) {
            $this->errors['second_name'] = 'Second name must not exceed 50 characters';
            return;
        }

        if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $secondName)) {
            $this->errors['second_name'] = 'Second name contains invalid characters';
            return;
        }

        $this->validatedData['second_name'] = trim($secondName);
    }

    private function validateBirthdate(?string $birthdate): void
    {
        if (empty($birthdate)) {
            $this->errors['birthdate'] = 'Birthdate is required';
            return;
        }

        if (!is_string($birthdate)) {
            $this->errors['birthdate'] = 'Birthdate must be a string';
            return;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$date || $date->format('Y-m-d') !== $birthdate) {
            $this->errors['birthdate'] = 'Birthdate must be in format YYYY-MM-DD';
            return;
        }

        $minDate = new \DateTime('1900-01-01');
        $maxDate = new \DateTime('now');

        if ($date < $minDate) {
            $this->errors['birthdate'] = 'Birthdate must be after 1900-01-01';
            return;
        }

        if ($date > $maxDate) {
            $this->errors['birthdate'] = 'Birthdate cannot be in the future';
            return;
        }

        $age = $date->diff(new \DateTime())->y;
        if ($age < 18) {
            $this->errors['birthdate'] = 'User must be at least 18 years old';
            return;
        }

        $this->validatedData['birthdate'] = $birthdate;
    }

    private function validateBiography(?string $biography): void
    {
        if (!is_string($biography)) {
            $this->errors['biography'] = 'Biography must be a string';
            return;
        }

        if (strlen($biography) > 1000) {
            $this->errors['biography'] = 'Biography must not exceed 1000 characters';
            return;
        }

        $this->validatedData['biography'] = trim($biography ?? '');
    }

    private function validateCity(?string $city): void
    {
        if (empty($city)) {
            $this->errors['city'] = 'City is required';
            return;
        }

        if (!is_string($city)) {
            $this->errors['city'] = 'City must be a string';
            return;
        }

        if (strlen($city) < 2) {
            $this->errors['city'] = 'City must be at least 2 characters long';
            return;
        }

        if (strlen($city) > 100) {
            $this->errors['city'] = 'City must not exceed 100 characters';
            return;
        }

        $this->validatedData['city'] = trim($city);
    }

    private function validatePassword(?string $password): void
    {
        if (empty($password)) {
            $this->errors['password'] = 'Password is required';
            return;
        }

        if (!is_string($password)) {
            $this->errors['password'] = 'Password must be a string';
            return;
        }

        if (strlen($password) < 6) {
            $this->errors['password'] = 'Password must be at least 6 characters long';
            return;
        }

        if (strlen($password) > 100) {
            $this->errors['password'] = 'Password must not exceed 100 characters';
            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors['password'] = 'Password must contain at least one uppercase letter';
            return;
        }

        if (!preg_match('/[a-z]/', $password)) {
            $this->errors['password'] = 'Password must contain at least one lowercase letter';
            return;
        }

        if (!preg_match('/\d/', $password)) {
            $this->errors['password'] = 'Password must contain at least one digit';
            return;
        }

        $this->validatedData['password'] = $password;
    }

    /**
     * Вспомогательный метод для валидации email (если понадобится в будущем)
     */
    private function validateEmail(?string $email): void
    {
        if (empty($email)) {
            $this->errors['email'] = 'Email is required';
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
            return;
        }

        $this->validatedData['email'] = strtolower(trim($email));
    }
}