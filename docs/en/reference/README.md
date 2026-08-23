# Operation reference

Every page below describes one public resource operation. Examples assume that the client or organization context has already been created as shown in [Installation and first client](../installation.md). All successful calls return `ApiResponse<T>`; see [responses and errors](../responses-and-errors.md) for metadata and exception behavior shared by every page.

## Resources

- [Organizations](./organizations/README.md) — 1 operation
- [Categories](./categories/README.md) — 3 operations
- [Offers](./offers/README.md) — 14 operations
- [Attachments](./attachments/README.md) — 4 operations
- [Orders](./orders/README.md) — 7 operations
- [Returns](./returns/README.md) — 5 operations
- [Claims](./claims/README.md) — 6 operations

The reference covers 40 current public resource operations. Do not call a resource’s constructor directly; get it from `VonHalskyClient` or `OrganizationContext`.
