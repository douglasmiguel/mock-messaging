# Transactional Outbox

<p align="center">
  <img src="assets/growth-loop-logo.png" alt="Growth Loop logo" width="120">
</p>

The transactional outbox makes a business-state change and its corresponding event reliable without trying to make the database and message broker a single distributed transaction.

## The core flow

```text
Client request
   |
   v
Order Service database transaction
   |- write the authoritative order state
   `- insert an unpublished outbox event
   |
   v
Commit succeeds or both writes roll back
   |
   v
Outbox publisher reads pending events
   |
   v
RabbitMQ topic exchange
   |
   +--> Notification Service
   +--> Rider Service
   `--> Observability Service
```

In this project, the Order Service owns the order and its outbox. When an order changes state, it writes both the order update and an event such as `order.placed` in the same local database transaction.

## Why it is needed

Without an outbox, the service performs an unsafe dual write:

1. Save the order in the database.
2. Publish an event to RabbitMQ.

Those are independent operations. If the order commits and the process stops before publishing, the order exists but downstream services never learn about it. If the event is published before the database commit and the transaction later fails, consumers can act on an order that does not exist.

The outbox changes the first step into one atomic database transaction:

1. Write the order state.
2. Insert the unpublished outbox record.
3. Commit both records together, or roll back both.
4. Publish the durable outbox record asynchronously.

## What rollback guarantees

If either SQL statement inside the transaction fails, the transaction rolls back. There is no committed order change without its outbox record, and no committed outbox record for an order change that did not commit.

That is important, but it is not the whole benefit. The most valuable failure case is after a successful commit:

> If the service stops after committing the order and outbox row, but before publishing to RabbitMQ, the pending outbox row remains durable. The publisher can send it later.

The client does not need to retry merely because the message broker was temporarily unavailable.

## Publisher behaviour

The publisher reads pending rows and publishes a persistent message to RabbitMQ. It waits for broker confirmation before marking the outbox row as published.

There is still a possible crash window:

```text
RabbitMQ accepts the message
        |
        v
Publisher stops before recording published_at
```

When the publisher restarts, it may send the same event again. This is expected. The design provides **at-least-once delivery**, rather than claiming unrealistic end-to-end exactly-once delivery.

## Consumer behaviour: make effects idempotent

Every event has a stable `event_id`. A consumer stores enough durable information to recognise an event it has already applied, then acknowledges RabbitMQ only after its local effect is safely committed.

For example, if the Notification Service sends an email and stops before acknowledging the message, RabbitMQ can redeliver it. The service uses the same event ID to avoid repeating the durable notification effect.

The practical rule is:

> Assume messages can be delivered more than once; make the business effect safe to repeat.

Retries, delayed retry queues, dead-letter queues, and reconciliation handle failures that cannot be resolved immediately.

## Interview-ready explanation

> I would use a transactional outbox when a committed business-state change must reliably trigger asynchronous work. The service writes the authoritative state change and a versioned event in one database transaction. If either write fails, both roll back. After commit, an independent publisher retries pending outbox records until the broker confirms receipt. Because a crash can still create duplicate delivery, consumers use a stable event ID or idempotency key and apply side effects only once. That gives atomic source state, reliable eventual publication, and duplicate-safe downstream processing.

## The key distinction

The transactional outbox does **not** make the database, RabbitMQ, and every consumer database one global transaction. It creates a reliable hand-off from the authoritative service to the asynchronous system. Each consumer remains responsible for its own idempotency, retry strategy, and reconciliation.
