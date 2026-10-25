# ADR-0004: OHTTP protects the client-to-gateway boundary only

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

Queries and documents submitted for AI processing are sensitive, and the
delivery path involves more parties than the client and AegisThread: an
OHTTP relay may forward requests so that it cannot link request contents to
the requesting client, and AegisThread itself sends processing to downstream
targets through the AI integration boundary. It is tempting — and wrong — to
describe this as end-to-end encryption. The honesty of the trust model is
itself a privacy requirement: overclaiming here would poison every
downstream privacy statement this project makes.

## Decision

AegisThread adopts **OHTTP (RFC 9458)** strictly for the client-to-gateway
boundary, with the following scope statements, which are normative for the
whole repository:

- OHTTP protects the **client-to-gateway boundary only**.
- The **gateway sees decapsulated request contents by design**; it must
  decapsulate requests in order to process them.
- The **relay never sees protected request contents**, but it does see the
  client's network identity and gateway URI metadata.
- OHTTP does **not** provide end-to-end protection to the AI provider or to
  any downstream target; protection ends at the gateway.
- **Meaningful unlinkability requires an independently operated relay and
  gateway**; relay and gateway operated by the same party restore the link
  the design intends to break.

## Rationale

- A precisely stated boundary is the only defensible basis for privacy
  documentation and review; a vague "encrypted submission" claim would be
  unverifiable.
- These statements define what any AegisThread document may claim: anything
  beyond them is a defect. The full trust model is elaborated in the
  [system design: OHTTP integration and trust model](../architecture/system-design.md#ohttp-integration-and-trust-model).
- The threat model is explicitly deferred (OQ-12), which makes precision now
  more important: the recorded scope is what reviewers can hold the design
  to.

## Alternatives considered

- **Plain TLS to the gateway without a relay.** Rejected as the protected
  submission path: the gateway learns the client's network identity together
  with the content, and no forwarding party exists to separate identity from
  content.
- **Describing OHTTP as end-to-end encryption to the AI provider.** Rejected:
  false, and directly contradicted by the decision above.
- **Not protecting the submission path at all.** Rejected: multi-party
  exposure of queries and content is a named product problem
  ([product vision](../product/product-vision.md#problems)).

## Consequences

- Deployment requires an independently operated relay; the hosting topology
  is an open decision (OQ-13).
- Gateway key lifecycle — generate, advertise, rotate, retire — becomes an
  operational requirement (OQ-08).
- The candidate PHP library and its integration are governed by
  [ADR-0005](0005-ohttp-behind-transport-abstraction.md).
- No AegisThread document may claim anonymity, untraceability, or end-to-end
  protection beyond the scope recorded here.

## Links

- [ADR-0005](0005-ohttp-behind-transport-abstraction.md) — integration behind a transport abstraction
- [System design: OHTTP integration and trust model](../architecture/system-design.md#ohttp-integration-and-trust-model)
- [Glossary: OHTTP](../product/glossary.md#ohttp), [Relay](../product/glossary.md#relay-ohttp), [Gateway](../product/glossary.md#gateway-ohttp), [Encapsulated request](../product/glossary.md#encapsulated-request)
- [Open questions register](../development/implementation-plan.md#open-questions-and-assumptions-register) — OQ-08, OQ-12, OQ-13
- RFC 9458: https://www.rfc-editor.org/rfc/rfc9458.html
