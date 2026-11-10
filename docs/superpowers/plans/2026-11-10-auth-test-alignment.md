# Authentication test alignment

User requirement: align the reported failing tests with the current system and report actual system defects.

- [x] Reproduce the reported failures and trace their causes.
- [x] Update dashboard fixtures to represent the current approval workflow without weakening KPI assertions.
- [x] Isolate password-reset tests from local tenant data and populate the actual tenant-bound verification contract.
- [x] Update the two-factor validation contract to its dedicated endpoint.
- [x] Find and remove the order-dependent permission-test state leak.
- [x] Run the affected tests individually and as a combined authentication regression set; report any genuine production defects separately.

Scope: test setup, fixtures, expectations and verification notes. Preserve unrelated working changes. Do not change production behavior to satisfy a stale test or send real email during tests.

Results: 215 passed, 7 skipped in the combined regression set; 71 passed in randomized affected tests (seed 20261110). Production findings are recorded in `docs/testing/auth-test-alignment.md`.
