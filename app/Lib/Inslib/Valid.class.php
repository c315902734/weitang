<?php
class Valid
{
	/**
	 * 验证是否指定域名邮箱
	 *
	 * @param   string   $email  A email address
	 * @param   array   $at  some domain address
	 * @return  boolean
	 */
	public static function email_at($email, $at)
	{
		return (bool) @preg_match("/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(" . $at . ")$/i", $email);
	}

	/**
	 * Validate an email address. This method is more strict than Valid::email_rfc();
	 *
	 * ###### Example:
	 *
	 *     $email = 'bill@gates.com';
	 *
	 *     Kohana::debug(Valid::email($email));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $email  A email address
	 * @return  boolean
	 */
	public static function email($email)
	{
		return (bool) preg_match('/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD', (string) $email);
	}

	/**
	 * Validate the domain of an email address by checking if the domain has a
	 * valid MX record.
	 *
	 * [!!] This function will always return `TRUE` if the checkdnsrr() function isn't avaliable (All Windows platforms before php 5.3)
	 *
	 * ###### Example:
	 *
	 *     $email = 'bill@gates.com';
	 *
	 *     Kohana::debug(Valid::email_domain($email));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $email  Email address
	 * @return  boolean
	 */
	public static function email_domain($email)
	{
		// If we can't prove the domain is invalid, consider it valid
		// Note: checkdnsrr() is not implemented on Windows platforms
		if ( ! function_exists('checkdnsrr'))
			return TRUE;

		// Check if the email domain has a valid MX record
		return (bool) checkdnsrr(preg_replace('/^[^@]+@/', '', $email), 'MX');
	}

	/**
	 * RFC compliant email validation. This function is __LESS__ strict than [Valid::email]. Choose carefully.
	 *
	 * ###### Example:
	 *
	 *     $email = 'bill@gates.com';
	 *
	 *     Kohana::debug(Valid::email_rfc($email));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @see  Originally by Cal Henderson, modified to fit Kohana syntax standards:
	 * @see  http://www.iamcal.com/publish/articles/php/parsing_email/
	 * @see  http://www.w3.org/Protocols/rfc822/
	 *
	 * @param   string   $email  Email address
	 * @return  boolean
	 */
	public static function email_rfc($email)
	{
		$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
		$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
		$atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
		$pair  = '\\x5c[\\x00-\\x7f]';

		$domain_literal = "\\x5b($dtext|$pair)*\\x5d";
		$quoted_string  = "\\x22($qtext|$pair)*\\x22";
		$sub_domain     = "($atom|$domain_literal)";
		$word           = "($atom|$quoted_string)";
		$domain         = "$sub_domain(\\x2e$sub_domain)*";
		$local_part     = "$word(\\x2e$word)*";
		$addr_spec      = "$local_part\\x40$domain";

		return (bool) preg_match('/^'.$addr_spec.'$/D', (string) $email);
	}

	/**
	 * Basic URL validation.
	 *
	 * ###### Example:
	 *
	 *     $url = 'http://www.kohanaphp.com';
	 *
	 *     Kohana::debug(Valid::url($url));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string  $url URL
	 * @return  boolean
	 */
	public static function url($url)
	{
		return (bool) filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
	}

	/**
	 * Validates an IP Address. This only tests to see if the ip address is valid,
	 * it doesn't check to see if the ip address is actually in use. Has optional support for
	 * IPv6, and private ip address ranges.
	 *
	 * ###### Example:
	 *
	 *     $ip_address = '127.0.0.1';
	 *
	 *     Kohana::debug(Valid::ip($ip_address));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $ip             IP address
	 * @param   boolean  $ipv6           Allow IPv6 addresses
	 * @param   boolean  $allow_private  Allow private IP networks
	 * @return  boolean
	 */
	public static function ip($ip, $ipv6 = FALSE, $allow_private = TRUE)
	{
		// By default do not allow private and reserved range IPs
		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
		if ($allow_private === TRUE)
			$flags =  FILTER_FLAG_NO_RES_RANGE;

		if ($ipv6 === TRUE)
			return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);

		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags | FILTER_FLAG_IPV4);
	}

	/**
	 * Validates a credit card number using the [Luhn (mod10)](http://en.wikipedia.org/wiki/Luhn_algorithm)
	 * formula.
	 *
	 * ###### Example:
	 *
	 *     // This is the standard Visa/Mastercard/AMEX test credit card number...
	 *     $cc_number = '4111111111111111';
	 *
	 *     Kohana::debug(Valid::credit_card($cc_num, array('visa', 'mastercard')));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   integer       $number  Credit card number
	 * @param   string|array  $type    Card type, or an array of card types
	 * @return  boolean
	 */
	public static function credit_card($number, $type = NULL)
	{
		/* invalid use from kohana  Kohana::config */
		return FALSE;

		if ($type == NULL)
		{
			// Use the default type
			$type = 'default';
		}
		elseif (is_array($type))
		{
			foreach ($type as $t)
			{
				// Test each type for validity
				if (Valid::credit_card($number, $t))
					return TRUE;
			}

			return FALSE;
		}

		//$cards = Kohana::config('credit_cards');

		// Remove all non-digit characters from the number
		if ($cards[$type]['luhn'] === TRUE)
		{
			if (($number = preg_replace('/\D+/', '', $number)) === '')
				return FALSE;
		}

		// Check card type
		$type = strtolower($type);

		if ( ! isset($cards[$type]))
			return FALSE;

		// Check card number length
		$length = strlen($number);

		// Validate the card length by the card type
		if ( ! in_array($length, preg_split('/\D+/', $cards[$type]['length'])))
			return FALSE;

		// Check card number prefix
		if ( ! preg_match('/^'.$cards[$type]['prefix'].'/', $number))
			return FALSE;

		// No Luhn check required
		if ($cards[$type]['luhn'] == FALSE)
			return TRUE;

		// Checksum of the card number
		$checksum = 0;

		for ($i = $length - 1; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit, starting from the right
			$checksum += substr($number, $i, 1);
		}

		for ($i = $length - 2; $i >= 0; $i -= 2)
		{
			// Add up every 2nd digit doubled, starting from the right
			$double = substr($number, $i, 1) * 2;

			// Subtract 9 from the double where value is greater than 10
			$checksum += ($double >= 10) ? $double - 9 : $double;
		}

		// If the checksum is a multiple of 10, the number is valid
		return ($checksum % 10 === 0);
	}

	/**
	 * Checks if a phone number is valid. This function will strip all non-digit
	 * characters from the phone number for testing.
	 *
	 * ###### Example:
	 *
	 *     $phone_number = '(201) 664-0274';
	 *
	 *     Kohana::debug(Valid::phone($phone_number));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $number   Phone number to check
	 * @param   array    $lengths  Valid lengths
	 * @return  boolean
	 */
	public static function phone($number, $lengths = NULL)
	{
		if ( ! is_array($lengths))
		{
			$lengths = array(7,10,11);
		}

		// Remove all non-digit characters from the number
		$number = preg_replace('/\D+/', '', $number);

		// Check if the number is within range
		return in_array(strlen($number), $lengths);
	}

	/**
	 * Tests if a string is a valid date using the php
	 * [strtotime()](http://php.net/strtotime) function.
	 *
	 * ###### Example:
	 *
	 *     $date = '12/12/12';
	 *
	 *     Kohana::debug(Valid::date($date));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $str  Date to check
	 * @return  boolean
	 */
	public static function date($str)
	{
		return (strtotime($str) !== FALSE);
	}

	/**
	 * Checks whether a string consists of alphabetical characters only.
	 *
	 * ###### Example:
	 *
	 *     $str = 'abcdefghijklmnopqrstuvwxyz';
	 *
	 *     Kohana::debug(Valid::alpha($str));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $str   Input string
	 * @param   boolean  $utf8  Trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^\pL++$/uD', (string) $str)
			: ctype_alpha((string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters and numbers only.
	 *
	 * ###### Example:
	 *
	 *     $str = 'abcdefghijklmnopqrstuvwxyz1234567890*****';
	 *
	 *     Kohana::debug(Valid::alpha_numeric($str));
	 *
	 *     // Output:
	 *     (boolean) false
	 *
	 * @param   string   $str   Input string
	 * @param   boolean  $utf8  Trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_numeric($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^[\pL\pN]++$/uD', (string) $str)
			: ctype_alnum((string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters, numbers, underscores and dashes only.
	 *
	 * ###### Example:
	 *
	 *     $str = 'abcdefghijklmnopqrstuvwxyz_-';
	 *
	 *     Kohana::debug(Valid::alpha_dash($str));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $str  Input string
	 * @param   boolean  $utf8  Trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_dash($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^[-\pL\pN_]++$/uD', (string) $str)
			: (bool) preg_match('/^[-a-z0-9_]++$/iD', (string) $str);
	}

	/**
	 * Checks whether a string consists of alphabetical characters and spaces only.
	 *
	 * ###### Example:
	 *
	 *     $str = 'abc defghijkl mnopqrstuv wxyz';
	 *
	 *     Kohana::debug(Valid::alpha_space($str));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $str  Input string
	 * @param   boolean  $utf8  Trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function alpha_space($str, $utf8 = FALSE)
	{
		return ($utf8 === TRUE)
			? (bool) preg_match('/^[\pL\s]++$/uD', (string) $str)
			: (bool) preg_match('/^[a-z\s]++$/iD', (string) $str);
	}

	/**
	 * Checks whether a string consists of digits only (no dots or dashes).
	 *
	 * ###### Example:
	 *
	 *     $str = '23';
	 *
	 *     Kohana::debug(Valid::digit('23'));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $str    Input string
	 * @param   boolean  $utf8   Trigger UTF-8 compatibility
	 * @return  boolean
	 */
	public static function digit($str, $utf8 = FALSE)
	{
		if ($utf8 === TRUE)
		{
			return (bool) preg_match('/^\pN++$/uD', $str);
		}
		else
		{
			return (is_int($str) AND $str >= 0) OR ctype_digit($str);
		}
	}

	/**
	 * Checks whether a string is a valid number (negative and decimal numbers allowed).
	 * This function uses [localeconv()](http://www.php.net/manual/en/function.localeconv.php)
	 * to support international number formats.
	 *
	 * ###### Example:
	 *
	 *     Kohana::debug(Valid::numeric('2.3'));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param   string   $str  Input string
	 * @return  boolean
	 */
	public static function numeric($str)
	{
		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		return (bool) preg_match('/^-?[0-9'.$decimal.']++$/D', (string) $str);
	}

	/**
	 * Tests if a number is within a range.
	 *
	 * @param   string   number to check
	 * @param   integer  minimum value
	 * @param   integer  maximum value
	 * @return  boolean
	 */
	public static function range($number, $min, $max)
	{
		return ($number >= $min AND $number <= $max);
	}

	/**
	 * Checks if a string is a proper decimal format. Optionally, a specific
	 * number of digits can be checked too.
	 *
	 * @param   string   number to check
	 * @param   integer  number of decimal places
	 * @param   integer  number of digits
	 * @return  boolean
	 */
	public static function decimal($str, $places = 2, $digits = NULL)
	{
		if ($digits > 0)
		{
			// Specific number of digits
			$digits = '{'.(int) $digits.'}';
		}
		else
		{
			// Any number of digits
			$digits = '+';
		}

		// Get the decimal point for the current locale
		list($decimal) = array_values(localeconv());

		return (bool) preg_match('/^[0-9]'.$digits.preg_quote($decimal).'[0-9]{'.(int) $places.'}$/D', $str);
	}

	/**
	 * Checks if a string is a proper hexadecimal HTML color value. The validation
	 * is quite flexible as it does not require an initial "#" and also allows for
	 * the short notation using only three instead of six hexadecimal characters.
	 *
	 * @param   string   input string
	 * @return  boolean
	 */
	public static function color($str)
	{
		return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
	}

	/**
	 * Performs a simple test using the modulo operator to see if a given
	 * divisor is a multiple of the given dividend.
	 *
	 * [!!] Due to the need for an extra argument, this method does not play nice with the Validation library.
	 *
	 * ###### Example:
	 *
	 *     Kohana::debug(Valid::multiple(200, 50));
	 *
	 *     // Output:
	 *     (boolean) true
	 *
	 * @param	integer	  $dividend  Dividend
	 * @param	integer	  $divisor  Divisor
	 * @return	boolean
	 */
	public static function multiple($dividend, $divisor)
	{
		// Note: this needs to be reversed because modulo returns a zero remainder for a true multiple
		return ! (bool) ((int) $dividend % (int) $divisor);
	}

	/**
	 * Check if the mobile is valid
	 *
	 * @param 	integer   $mobile
	 * @return 	boolean
	 */
	public static function mobile($mobile)
	{
		return strlen($mobile) === 11 && is_numeric($mobile) && preg_match('/^1[358]\d{9}$/', $mobile);
	}
}