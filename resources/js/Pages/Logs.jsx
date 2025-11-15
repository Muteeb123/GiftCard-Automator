import React, { useEffect, useState } from "react";
import {
    Page,
    Card,
    DataTable,
    Pagination,
    Spinner,
    Badge,
    BlockStack,
    Text,
} from "@shopify/polaris";
import { router, usePage } from "@inertiajs/react";

export default function GiftCardLogs({ batchId }) {
    const [giftCards, setGiftCards] = useState([]);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState({});
    const [loading, setLoading] = useState(true);

    const ziggy = usePage().props;
    const query = ziggy?.ziggy?.query || {};

    // ✅ Fetch generated gift cards for this batch
    const fetchGiftCards = async (pageNum = 1) => {
        setLoading(true);
        console.log(batchId);
        try {
            const url = route("giftcards.logs", { batch: batchId.id, ...query, page: pageNum });
            const response = await fetch(url);
            const data = await response.json();
            setGiftCards(data.data || []);
            setMeta({
                current_page: data.current_page,
                last_page: data.last_page,
            });
        } catch (error) {
            console.error("Error fetching logs:", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchGiftCards(page);
    }, [page]);

    // ✅ Use batch details directly (no need to parse each card for them)
    const emailList =
        batchId.email_list && batchId.email_list !== "null"
            ? JSON.parse(batchId.email_list).join(", ")
            : "—";
    console.log('Email: ', emailList);
    const expiresIn = batchId.gift_card_expiry
        ? new Date(batchId.gift_card_expiry).toLocaleDateString()
        : "—";

    const cardValue = `$${batchId.card_value}`;

    const rows = giftCards.map((card) => [
        <Text as="span" fontWeight="medium">
            {card.code}
        </Text>,
        cardValue,
        <Badge
            tone={
                card.status === "created"
                    ? "success"
                    : card.status === "failed"
                        ? "critical"
                        : "attention"
            }
        >
            {card.status}
        </Badge>,
        new Date(card.created_at).toLocaleDateString(),
        expiresIn,
        emailList,
    ]);

    return (
        <Page
            title={`Generated Gift Cards (Batch #${batchId.id})`}
            backAction={{
                content: "Back to Batches",
                onAction: () => router.visit(route("giftcards.page", query)),
            }}
        >
            <BlockStack gap="400">
                <Card>
                    {loading ? (
                        <div style={{ textAlign: "center", padding: "2rem" }}>
                            <Spinner accessibilityLabel="Loading logs" size="large" />
                        </div>
                    ) : (
                        <>
                            <DataTable
                                columnContentTypes={[
                                    "text",
                                    "text",
                                    "text",
                                    "text",
                                    "text",
                                    "text",
                                ]}
                                headings={[
                                    "Gift Card Code",
                                    "Value ($)",
                                    "Status",
                                    "Created Date",
                                    "Expires At",
                                    "Email",
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
