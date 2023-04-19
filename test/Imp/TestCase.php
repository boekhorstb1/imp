<?php

use IMP_Smime;

class TestCase extends PHPUnit\Framework\TestCase
{
    protected function getLibSmimeClass(){
        $hordeCryptSmime = $this->createMock(IMP_Smime::class);
        return $hordeCryptSmime;
    }

}