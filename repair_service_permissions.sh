#!/bin/bash

repair()
{
sudo chmod -R 775 $1
sudo chown -R apache $1
sudo chgrp -R webadmins $1
}

repair services/v1/
repair services/v2/
repair services/migration/
