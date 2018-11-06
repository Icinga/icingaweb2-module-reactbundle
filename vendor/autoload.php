<?php

namespace Icinga\Module\Reactbundle {

    use Icinga\Application\Hook\ApplicationStateHook;

    class ApplicationState extends ApplicationStateHook
    {
        public function collectMessages()
        {
            $this->addError(
                'reactbundle.master',
                time(),
                'Please install a Release version of the Reactbundle module, not the GIT master'
            );
        }
    }

    $this->provideHook('ApplicationState', '\\Icinga\\Module\\Reactbundle\\ApplicationState');
}
