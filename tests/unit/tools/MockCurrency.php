<?php

class MockCurrency extends Currency
{
	public function __construct($id = null, $idLang = null, $idShop = null)
	{
		$this->id = $id;
		$this->id_lang = $idLang;
		$this->id_shop = $idShop;
	}

	public function getMode()
	{
		return true;
	}

}