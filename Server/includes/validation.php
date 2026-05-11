<?php
/**
 * Input Validation Functions
 */

class Validation {

    public static function validateRegisterInput($data) {
        $errors = [];

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!Security::validateEmail($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } else {
            $pwdCheck = Security::validatePasswordStrength($data['password']);
            if (!$pwdCheck['valid']) {
                $errors['password'] = $pwdCheck['error'];
            }
        }

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        // NID validation
        if (empty($data['nid'])) {
            $errors['nid'] = 'NID is required';
        } elseif (!Security::validateNID($data['nid'])) {
            $errors['nid'] = 'Invalid NID format';
        }

        // Age validation
        if (empty($data['age'])) {
            $errors['age'] = 'Age is required';
        } elseif (!Security::validateAge($data['age'])) {
            $errors['age'] = 'Age must be between 18 and 120';
        }

        return $errors;
    }

    public static function validateLoginInput($data) {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!Security::validateEmail($data['email'])) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        return $errors;
    }

    public static function validateMedicineInput($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Medicine name is required';
        }

        if (empty($data['brand'])) {
            $errors['brand'] = 'Brand is required';
        }

        if (empty($data['category'])) {
            $errors['category'] = 'Category is required';
        }

        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors['price'] = 'Valid price is required';
        }

        if (empty($data['stock_level']) || !is_numeric($data['stock_level']) || $data['stock_level'] < 0) {
            $errors['stock_level'] = 'Valid stock level is required';
        }

        if (empty($data['exp_date'])) {
            $errors['exp_date'] = 'Expiry date is required';
        } else {
            $expDate = DateTime::createFromFormat('Y-m-d', $data['exp_date']);
            if (!$expDate || $expDate->format('Y-m-d') !== $data['exp_date']) {
                $errors['exp_date'] = 'Invalid date format (use YYYY-MM-DD)';
            }
        }

        return $errors;
    }

    public static function validateCheckoutInput($data) {
        $errors = [];

        if (empty($data['order_id'])) {
            $errors['order_id'] = 'Order ID is required';
        }

        return $errors;
    }

    public static function validatePrescriptionReviewInput($data) {
        $errors = [];

        if (empty($data['review_id'])) {
            $errors['review_id'] = 'Review ID is required';
        }

        if (empty($data['decision']) || !in_array($data['decision'], ['Approved', 'Rejected'])) {
            $errors['decision'] = 'Decision must be Approved or Rejected';
        }

        return $errors;
    }

    public static function validateDrugConflictInput($data) {
        $errors = [];

        if (empty($data['drug1_id']) || !is_numeric($data['drug1_id'])) {
            $errors['drug1_id'] = 'Valid drug 1 ID required';
        }

        if (empty($data['drug2_id']) || !is_numeric($data['drug2_id'])) {
            $errors['drug2_id'] = 'Valid drug 2 ID required';
        }

        if (empty($data['description'])) {
            $errors['description'] = 'Conflict description is required';
        }

        if ($data['drug1_id'] === $data['drug2_id']) {
            $errors['general'] = 'Drugs cannot conflict with themselves';
        }

        return $errors;
    }
}

?>
