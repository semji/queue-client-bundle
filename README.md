# Queue Client Bundle

[![Join the chat at https://gitter.im/ReputationVIP/queue-client](https://badges.gitter.im/ReputationVIP/queue-client.svg)](https://gitter.im/ReputationVIP/queue-client?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

An easy way to use [queue client library](https://github.com/ReputationVIP/queue-client) in [Symfony](http://symfony.com) project with its own [Symfony](http://symfony.com) bundle.

## Available commands

- `queue-client:add-messages` Add message in queue
- `queue-client:create-queues` Create queues
- `queue-client:delete-queues` Delete queues
- `queue-client:get-messages` Get messages from queue
- `queue-client:list-priorities` List priorities
- `queue-client:purge-queues` Purge queues
- `queue-client:queues-info` Display queues information

Use `--help` option for command usage.

## Configuration

Add queue client configuration in config.yml and retrieve the service using Symfony container.

```
container->get('queue-client')
```

`queue_client` node needs an ```adapter``` node to define the adapter to be used.
`adapter` node must define a ```type``` parameter (see "Available adapter types")

Then add specific configuration for each ```type```.

```
queue_client:
    adapter:
        type: queue type
```

Sample configuration:
```
queue_client:
    queues_file: %kernel.root_dir%/config/queues.yml
    adapter:
        type: file
        repository: /tmp/queues
    priority_handler: 'ReputationVIP\QueueClient\PriorityHandler\ThreeLevelPriorityHandler'

```

### General configuration

- ```queues_file``` specifies the default [queues configuration file](doc/queues-configuration-file.md).
- ```queue_prefix``` specifies a queue prefix can use in [queues configuration file](doc/queues-configuration-file.md).
- ```priority_handler``` specifies the priority handler. Default is the `ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler`.

### Available adapter types

- ```null``` a black hole type.
- ```memory``` a memory type.
- ```file``` a file queue type.
- ```sqs``` a SQS queue type.

### File type Configuration
    
- ```repository```: this config value set the absolute path of the repository which contains queues files (default `/tmp/queues`).

### SQS type Configuration

- ```key:``` this config value set the SQS key.
- ```secret:``` this config value set the SQS secret.
- ```region:``` this config value set the SQS region (default `eu-west-1`).
- ```version:``` this config value set the SQS version (default `2012-11-05`).

