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
     * Boot the trait.
     */
    public static function bootEncryptsAttributes()
    {
        static::saving(function ($model) {
            $model->encryptAttributes();
        });

        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    /**
     * Encrypt the specified attributes.
     */
    public function encryptAttributes()
    {
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                try {
                    $this->attributes[$attribute] = $this->encrypt($this->attributes[$attribute]);
                } catch (\Exception $e) {
                    Log::error("Failed to encrypt attribute {$attribute}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Decrypt the specified attributes.
     */
    public function decryptAttributes()
    {
        foreach ($this->getEncryptedAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                try {
                    $this->attributes[$attribute] = $this->decrypt($this->attributes[$attribute]);
                } catch (\Exception $e) {
                    // If decryption fails, keep the original value
                    // This handles cases where data was stored unencrypted
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
            try {
                $value = $this->encrypt($value);
            } catch (\Exception $e) {
                Log::error("Failed to encrypt attribute {$key}: " . $e->getMessage());
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
