import React, { useEffect, useState } from "react";
import {
    Page,
    Card,
    DataTable,
    Pagination,
    Button,
    Text,
    BlockStack,
    Spinner,
    Badge,
    InlineStack,
} from "@shopify/polaris";
import { router, usePage } from "@inertiajs/react";

export default function GiftCardBatchList() {
    const [batches, setBatches] = useState([]);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState({});
    const [loading, setLoading] = useState(true);

    const pageProps = usePage().props;
    const query = pageProps?.ziggy?.query || {};

    // ===== FETCH BATCHES =====
    const fetchBatches = async (pageNum = 1) => {
        setLoading(true);
        try {
            const url = route("giftcards.index", { ...query, page: pageNum });
            const response = await fetch(url);
            const data = await response.json();

            setBatches(data.data || []);
            setMeta({
                current_page: data.current_page,
                last_page: data.last_page,
            });
        } catch (error) {
            console.error("Error fetching batches:", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchBatches(page);
    }, [page]);

    // ===== HANDLERS =====
    const handleDownload = (batchId) => {
        const url = route("giftcards.download", { ...query, id: batchId });
        window.open(url, "_blank");
    };

    const handleViewLogs = (batchId) => {
        router.visit(route("giftcards.logs.page", { ...query, batch: batchId }));
    };

    // ===== HELPER: DAYS LEFT =====
    const getDaysLeft = (createdAt) => {
        const created = new Date(createdAt);
        const now = new Date();
        const diffMs = 3 * 24 * 60 * 60 * 1000 - (now - created);
        return Math.max(0, Math.ceil(diffMs / (24 * 60 * 60 * 1000)));
    };

    // ===== TABLE ROWS =====
    const rows = batches.map((batch) => {
        const createdDate = new Date(batch.created_at).toLocaleDateString();
        const daysLeft = getDaysLeft(batch.created_at);
        const isExpired = daysLeft <= 0;

        return [
            <Text as="span" fontWeight="medium">
                #{batch.id}
            </Text>,
            `$${batch.card_value}`,
            <Text as="span">{batch.gift_card_count}</Text>,

            // ✅ Disable download if expired
            <BlockStack gap={100} align="center">
                <Button
                    size="slim"
                    variant="primary"
                    onClick={() => handleDownload(batch.id)}
                    disabled={isExpired}
                >
                    Download
                </Button>
                {!isExpired && (
                    <Text
                        as="span"
                        variant="bodySm"
                        tone="info"
                        fontWeight="medium"
                        textDecorationLine="underline"
                    >
                        Expires in {daysLeft} day{daysLeft !== 1 ? "s" : ""}
                    </Text>
                )}
                {isExpired && (
                    <Text
                        as="span"
                        variant="bodySm"
                        tone="critical"
                        fontWeight="medium"
                        textDecorationLine="underline"
                    >
                        Expired
                    </Text>
                )}
            </BlockStack>,

            batch.gift_card_length,

            // ✅ Created Date + “Expires in X days”

            <Text as="span">{createdDate}</Text>

            ,

            batch.prefix || "—",
            <Badge
                tone={
                    batch.status === "success"
                        ? "success"
                        : batch.status === "partial_failed"
                            ? "attention"
                            : "critical"
                }
            >
                {batch.status}
            </Badge>,
            <Button
                size="slim"
                variant="secondary"
                onClick={() => handleViewLogs(batch.id)}
            >
                View Logs
            </Button>,
        ];
    });

    // ===== RENDER =====
    return (
        <Page
            title="Gift Card Batches"
            backAction={{ content: "Back", onAction: () => window.history.back() }}
            primaryAction={{
                content: "Create New Batch",
                onAction: () => router.visit(route("create.giftcard.batch", query)),
            }}
        >
            <BlockStack gap="400">
                <Card>
                    {loading ? (
                        <div style={{ textAlign: "center", padding: "2rem" }}>
                            <Spinner accessibilityLabel="Loading batches" size="large" />
                        </div>
                    ) : (
                        <>
                            <DataTable
                                columnContentTypes={[
                                    "text",
                                    "text",
                                    "numeric",
                                    "text",
                                    "numeric",
                                    "text",
                                    "text",
                                    "text",
                                    "text",
                                ]}
                                headings={[
                                    "Batch ID",
                                    "Value",
                                    "Count",
                                    "Download",
                                    "Length",
                                    "Created Date",
                                    "Prefix",
                                    "Status",
                                    "Logs",
                                ]}
                                rows={rows}
                                hoverable
                            />

                            <div
                                style={{
                                    display: "flex",
                                    justifyContent: "center",
                                    marginTop: "1rem",
                                }}
                            >
                                <Pagination
                                    hasPrevious={meta.current_page > 1}
                                    onPrevious={() => setPage((p) => p - 1)}
                                    hasNext={meta.current_page < meta.last_page}
                                    onNext={() => setPage((p) => p + 1)}
                                />
                            </div>
                        </>
                    )}
                </Card>
            </BlockStack>
        </Page>
    );
}
