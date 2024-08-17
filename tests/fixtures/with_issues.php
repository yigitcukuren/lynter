<?php

// Restricted functions
eval('echo "This should be restricted";');
shell_exec('ls');

// Non-restricted function
nonRestrictedFunction();
