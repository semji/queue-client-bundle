# Queues configuration file

## Usage

this file describes your queues structure.
It requires a main node `queues` with one sub node for each queue. 

## Sample

```yaml
queue_client:
  queues:
    queue1:
        name: queue1
        aliases:
          - queue1-alias1
          - queue1-alias2
    queue2:
        name: "%queue_client.queue_prefix%-queue2"
        aliases:
          - queue2-alias1
          - queue2-alias2
    queue3:
        name: queue3
        aliases:
          - queue3-alias1
          - queue3-alias2
```
