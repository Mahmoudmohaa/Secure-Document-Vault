<?php

// This class implements the TOTP algorithm (RFC 6238)
// for generating and verifying 2FA codes

class SimpleTOTP {
    
    // Generate a random Base32 secret key
    public static function generateSecret($length = 16) {
        $b32 = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $s = "";
        for ($i = 0; $i < $length; $i++) {
            $s .= $b32[random_int(0, 31)];
        }
        return $s;
    }

    // Verify the code entered by the user
    public static function verifyCode($secret, $code) {
        
        // Check the current, previous, and next 30-second window
        // to avoid issues caused by time differences
        
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            if (self::calculateCode($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    // Calculate the TOTP code using HMAC-SHA1
    private static function calculateCode($secret, $timeSlice) {

        $secretKey = self::base32Decode($secret);

        $time = pack("N", 0) . pack("N", $timeSlice);

        $hash = hash_hmac('sha1', $time, $secretKey, true);

        $offset = ord(substr($hash, -1)) & 0x0F;

        $value = unpack('N', substr($hash, $offset, 4));

        $value = $value[1] & 0x7FFFFFFF;

        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    // Decode Base32 secret key
    private static function base32Decode($secret) {

        if (empty($secret)) return '';

        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

        $base32charsFlipped = array_flip(str_split($base32chars));

        $paddingCharCount = substr_count($secret, '=');

        $allowedValues = array(6, 4, 3, 1, 0);

        if (!in_array($paddingCharCount, $allowedValues)) return false;

        $secret = str_replace('=', '', $secret);

        $secret = str_split($secret);

        $binaryString = '';

        for ($i = 0; $i < count($secret); $i = $i + 8) {

            $x = '';

            if (!in_array($secret[$i], str_split($base32chars))) return false;

            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(
                    base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2),
                    5,
                    '0',
                    STR_PAD_LEFT
                );
            }

            $eightBits = str_split($x, 8);

            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (
                    ($y = chr(base_convert($eightBits[$z], 2, 10))) ||
                    ord($y) == 48
                ) ? $y : '';
            }
        }

        return $binaryString;
    }
}

?>