<?php

class NibblerExport
{
    public $additional_params = array(
        'flatten' => 1
    );

    private $__mapping = array(
        'ip-dst' => 'IP',
        'ip-src' => 'IP',
        'domain' => 'Domain',
        'domain|ip' => ['Domain', 'IP'],
        'hostname' => 'Hostname',
        'md5' => 'MD5',
        'sha1' => 'SHA1',
        'sha256' => 'SHA256',
        'filename|md5' => array('Filename', 'MD5'),
        'malware-sample' => array('Filename', 'MD5'),
        'filename|sha1' => array('Filename', 'SHA1'),
        'filename|sha256' => array('Filename', 'SHA256')
    );

    private function __escapeSpecialChars($value)
    {
        $value = preg_replace("/\r|\n/", "##LINEBREAK##", $value );
        $value = preg_replace("/,/", "##COMMA##", $value );
        $value = preg_replace("/\|/", "##PIPE##", $value );
        return $value;
    }

    private function __convertAttribute($attribute, $event)
    {
        if (empty($this->__mapping[$attribute['type']])) {
            return '';
        }
        $result = array();
        $attributes = array();
        if (is_array($this->__mapping[$attribute['type']])) {
            $attribute['value'] = explode('|', $attribute['value']);
            foreach (array(0,1) as $part) {
                $result[] = sprintf(
                    '%s|%s|%s|%s|%s',
                    $this->__escapeSpecialChars($attribute['value'][$part]),
                    $this->__mapping[$attribute['type']][$part],
                    '/events/view/' . $event['uuid'],
                    $this->__escapeSpecialChars($event['info']),
                    $this->__decideOnAction($attribute['AttributeTag'])
                );
            }
        } else {
            $result[] = sprintf(
                '%s|%s|%s|%s|%s',
                $this->__escapeSpecialChars($attribute['value']),
                $this->__mapping[$attribute['type']],
                '/events/view/' . $event['uuid'],
                $this->__escapeSpecialChars($event['info']),
                $this->__decideOnAction($attribute['AttributeTag'])
            );
        }
        return implode($this->separator(), $result);
    }

    private function __decideOnAction($attributeTags)
    {
        foreach($attributeTags as $attributeTag) {
            if (
                $attributeTag['Tag']['name'] === 'nibbler:block'
            ) {
                return 'BLOCK';
            }
        }
        return 'ALERT';
    }

    public function handler($data, $options = array())
    {
        if ($options['scope'] === 'Attribute') {
            $data['Attribute']['AttributeTag'] = $data['AttributeTag'];
            return $this->__convertAttribute($data['Attribute'], $data['Event']);
        }
        if ($options['scope'] === 'Event') {
            $result = array();
            foreach ($data['Attribute'] as $attribute) {
                $temp = $this->__convertAttribute($attribute, $data['Event']);
                if ($temp) $result[] = $temp;
            }
            return implode($this->separator(), $result);
        }
        return '';
    }

    public function header($options = array())
    {
        return sprintf(
            "# Nibbler rules generated by MISP at %s\n",
            date('Y-m-d H:i:s')
        );
    }

    public function footer()
    {
        return "\n";
    }

    public function separator()
    {
        return "\n";
    }
}