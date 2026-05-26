<?php

declare(strict_types=1);

namespace Drago\Keycloak;

use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Presenter;


/** Base presenter with backlink support. */
class BasePresenter extends Presenter
{
	#[Persistent]
	public string $backlink = '';
}
