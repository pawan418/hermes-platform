<?php

use Illuminate\Support\Facades\Schedule;

// Run Laravel queue prune every day to keep the database size optimized
Schedule::command('model:prune')->daily();
