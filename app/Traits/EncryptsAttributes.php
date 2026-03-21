<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait EncryptsAttributes
{
    /**
     * The attributes that should be encrypted.
     * This should be defined in the model using this trait.
     * Models using this trait must define their own $encrypted property.
     */

    /**
     * Encryption/decryption is handled entirely by setAttribute() and getAttribute()
     * so that $this->attributes always holds ciphertext and dirty-tracking works
     * correctly (no spurious UPDATEs).
     *
     * The public encryptAttributes() / decryptAttributes() methods below are kept
     * for explicit use in backfill commands or testing only – they are no longer
     * called automatically by Eloquent lifecycle events.
     */

    /**
     * Override attributesToArray() so that toArray(), toJson(), and JSON
     * serialisation all return decrypted values, not raw ciphertext.
     *
     * Eloquent's default attributesToArray() builds its result directly from
     * $this->attributes (ciphertext) without calling getAttribute(), so this
     * override is required to decrypt encrypted fields in the array output.
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getEncryptedAttributes() as $key) {
            if (array_key_exists($key, $attributes) && !empty($attributes[$key])) {
                try {
                    $attributes[$key] = $this->decrypt($attributes[$key]);
                } catch (\Exception $e) {
                    // If decryption fails keep original (handles legacy plaintext rows)
                }
            }
        }

        return $attributes;
    }

    /**
     * Encrypt all encrypted attributes in-place on the current model instance.
     * Useful for one-off backfill commands; does NOT persist to the database.
     */
    public function encryptAttributes(): void
    {
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                if ($this->isAlreadyEncrypted($this->attributes[$attribute])) {
                    continue;
                }
                try {
                    $this->attributes[$attribute] = $this->encrypt($this->attributes[$attribute]);
                } catch (\Exception $e) {
                    Log::error("Failed to encrypt attribute {$attribute}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Decrypt all encrypted attributes in-place on the current model instance.
     * Useful for one-off backfill commands; does NOT persist to the database.
     */
    public function decryptAttributes(): void
    {
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                try {
                    $this->attributes[$attribute] = $this->decrypt($this->attributes[$attribute]);
                } catch (\Exception $e) {
                    Log::warning("Failed to decrypt attribute {$attribute}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get an attribute value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->getEncryptedAttributes()) && !empty($value)) {
            try {
                return $this->decrypt($value);
            } catch (\Exception $e) {
                // If decryption fails, return the original value
                return $value;
            }
        }

        return $value;
    }

    /**
     * Set an attribute value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->getEncryptedAttributes()) && !empty($value)) {
            // Only encrypt if the value is not already a valid ciphertext.
            // This prevents double-encryption when a model is re-saved after retrieval.
            if (!$this->isAlreadyEncrypted($value)) {
                try {
                    $value = $this->encrypt($value);
                } catch (\Exception $e) {
                    Log::error("Failed to encrypt attribute {$key}: " . $e->getMessage());
                }
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Encrypt a value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function encrypt($value)
    {
        if (is_null($value)) {
            return $value;
        }

        return Crypt::encrypt($value);
    }

    /**
     * Decrypt a value.
     *
     * @param  string  $value
     * @return mixed
     */
    protected function decrypt($value)
    {
        if (is_null($value) || empty($value)) {
            return $value;
        }

        try {
            return Crypt::decrypt($value);
        } catch (\Exception $e) {
            // Return original value if decryption fails
            return $value;
        }
    }

    /**
     * Determine whether a value is already a valid Laravel ciphertext.
     * Used by setAttribute() to prevent double-encryption on re-save.
     */
    protected function isAlreadyEncrypted(mixed $value): bool
    {
        if (!is_string($value) || empty($value)) {
            return false;
        }

        try {
            Crypt::decrypt($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the encrypted attributes.
     *
     * @return array
     */
    public function getEncryptedAttributes()
    {
        return property_exists($this, 'encrypted') ? $this->encrypted : [];
    }

    /**
     * Add an attribute to the encrypted list.
     *
     * @param  string  $attribute
     * @return $this
     */
    public function addEncryptedAttribute($attribute)
    {
        if (!property_exists($this, 'encrypted')) {
            $this->encrypted = [];
        }
        
        if (!in_array($attribute, $this->getEncryptedAttributes())) {
            $this->encrypted[] = $attribute;
        }

        return $this;
    }

    /**
     * Remove an attribute from the encrypted list.
     *
     * @param  string  $attribute
     * @return $this
     */
    public function removeEncryptedAttribute($attribute)
    {
        if (!property_exists($this, 'encrypted')) {
            $this->encrypted = [];
        }
        
        $this->encrypted = array_values(array_filter($this->getEncryptedAttributes(), function ($item) use ($attribute) {
            return $item !== $attribute;
        }));

        return $this;
    }
}
