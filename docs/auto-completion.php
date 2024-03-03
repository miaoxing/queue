<?php

/**

 */
 #[\AllowDynamicProperties]
class AutoCompletion
{
}

/**
 * @return AutoCompletion|Wei\Wei
 */
function wei()
{
    return new AutoCompletion(func_get_args());
}
