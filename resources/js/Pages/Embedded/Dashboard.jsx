import React, { useState } from "react";
import {
    Card,
    TextField,
    InlineGrid,
    BlockStack,
    Page,
    Checkbox,
    Text,
    InlineStack,
    Button,
    Divider,
    Banner,
} from "@shopify/polaris";
import { usePage } from "@inertiajs/react";

export default function Dashboard() {
    const page = usePage().props;
    const query = page?.ziggy?.query;

    // 🧩 Extract user plan info from Laravel middleware (HandleInertiaRequests)
    const { plan, giftCardCount, limit } = page?.planInfo || {};
    const currentTotal = giftCardCount || 0;
    const planLimit = limit || 1000;

    // ====== STATE ======
    const [cardValue, setCardValue] = useState("");
    const [giftCardCountInput, setGiftCardCountInput] = useState("");
    const [giftCardLength, setGiftCardLength] = useState("");
    const [giftCardExpiry, setGiftCardExpiry] = useState("");
    const [addPrefix, setAddPrefix] = useState(false);
    const [prefixValue, setPrefixValue] = useState("");
    const [sendEmail, setSendEmail] = useState(false);
    const [emailList, setEmailList] = useState("");
    const [internalNote, setInternalNote] = useState("");
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    // ====== VALIDATION HELPERS ======
    const validateNumeric = (value) => {
        if (!value) return "This field is required";
        if (isNaN(value) || Number(value) <= 0) return "Must be a positive number";
        return "";
    };

    const validateEmails = (value) => {
        if (!value.trim()) return "At least one email required";
        const emails = value.split(",").map((e) => e.trim());
        const invalid = emails.find(
            (e) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)
        );
        if (invalid) return `Invalid email format: ${invalid}`;
        return "";
    };

    const validatePrefix = (value) => {
        if (!value.trim()) return "Prefix cannot be empty";
        if (value.length < 1 || value.length > 4)
            return "Prefix must be 1–4 characters";
        return "";
    };

    // ====== SUBMIT HANDLER ======
    const handleSubmit = async () => {
        setSubmitting(true);

        // Revalidate before sending
        const newErrors = {
            cardValue: validateNumeric(cardValue),
            giftCardCountInput: validateNumeric(giftCardCountInput),
            giftCardLength: validateNumeric(giftCardLength),
        };
        setErrors(newErrors);

        const hasError = Object.values(newErrors).some((e) => e);
        if (hasError) {
            setSubmitting(false);
            return;
        }

        // Check plan limit
        const totalAfter = Number(currentTotal) + Number(giftCardCountInput);
        console.log("Current Total: ", currentTotal, "Total After:", totalAfter, "Plan Limit:", planLimit, "Input Count:", giftCardCountInput);
        if (totalAfter > planLimit) {
            alert(
                `⚠️ You have reached your plan limit (${planLimit} cards).\nPlease upgrade your plan to create more gift cards.`
            );
            setSubmitting(false);
            return;
        }

        const payload = {
            card_value: cardValue,
            gift_card_count: giftCardCountInput,
            gift_card_length: giftCardLength,
            gift_card_expiry: giftCardExpiry || null,
            prefix: addPrefix ? prefixValue : null,
            email_list: sendEmail
                ? emailList
                    .split(",")
                    .map((e) => e.trim())
                    .filter((e) => e)
                : null,
            note: internalNote || null,
        };

        try {
            const response = await fetch(route("giftcards.store", query), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                alert("❌ Failed: " + JSON.stringify(data.errors));
            } else if (data.success) {
                alert(
                    `🎉 Gift card batch queued successfully (Batch #${data.batch_id})`
                );
            } else {
                alert("❌ Something went wrong.");
            }
        } catch (error) {
            alert("⚠️ Network or server error.");
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Page
            title="Gift Card Configuration"
            backAction={{
                content: "Back",
                onAction: () => console.log("Back clicked"),
            }}
        >
            <Divider />

            {/* ===== HEADER INFO ===== */}
            <div
                style={{
                    marginTop: "1rem",
                    position: "absolute",
                    right: "12rem",
                    top: "1rem",
                }}
            >
                <Text variant="bodyLg" as="p" tone="subdued" alignment="end">
                    No of Gift Cards Created{" "}
                    <strong>
                        {currentTotal}/{planLimit}
                    </strong>{" "}
                    | Plan: <strong>{plan}</strong>
                </Text>
            </div>

            <BlockStack gap="400" marginTop="4">
                {currentTotal >= planLimit && (
                    <Banner
                        title="Plan Limit Reached"
                        tone="warning"
                        action={{
                            content: "Upgrade Plan",
                            onAction: () => alert("Redirect to upgrade page..."),
                        }}
                    >
                        <p>
                            You’ve reached your gift card limit of{" "}
                            <strong>{planLimit}</strong> for the{" "}
                            <strong>{plan}</strong> plan. Upgrade to create more.
                        </p>
                    </Banner>
                )}

                {/* ===== CARD DETAILS ===== */}
                <Card>
                    <BlockStack gap="400">
                        <Text variant="headingMd" as="h2">
                            Card Details
                        </Text>

                        <InlineGrid columns={2} gap="300">
                            <TextField
                                label="Card Value"
                                type="number"
                                value={cardValue}
                                onChange={setCardValue}
                                error={errors.cardValue}
                                autoComplete="off"
                            />
                            <TextField
                                label="Gift Card Count"
                                type="number"
                                value={giftCardCountInput}
                                onChange={setGiftCardCountInput}
                                error={errors.giftCardCountInput}
                                autoComplete="off"
                            />
                        </InlineGrid>

                        <InlineGrid columns={2} gap="300">
                            <TextField
                                label="Gift Card Length"
                                type="number"
                                value={giftCardLength}
                                onChange={setGiftCardLength}
                                error={errors.giftCardLength}
                                autoComplete="off"
                            />
                            <TextField
                                label="Gift Card Expiry Date"
                                type="date"
                                value={giftCardExpiry}
                                onChange={setGiftCardExpiry}
                                error={errors.giftCardExpiry}
                                autoComplete="off"
                            />
                        </InlineGrid>
                    </BlockStack>
                </Card>

                {/* ===== PREFIX SECTION ===== */}
                <Card>
                    <BlockStack gap="400">
                        <InlineStack gap="200" align="left">
                            <Checkbox
                                checked={addPrefix}
                                onChange={setAddPrefix}
                            />
                            <Text variant="headingMd" as="h2">
                                Add Prefix to Gift Cards
                            </Text>
                        </InlineStack>

                        {addPrefix && (
                            <BlockStack gap="200">
                                <TextField
                                    label="Prefix"
                                    value={prefixValue}
                                    onChange={(val) =>
                                        setPrefixValue(val.toUpperCase())
                                    }
                                    onBlur={() =>
                                        setErrors((e) => ({
                                            ...e,
                                            prefixValue:
                                                validatePrefix(prefixValue),
                                        }))
                                    }
                                    error={errors.prefixValue}
                                    autoComplete="off"
                                    maxLength={4}
                                    placeholder="1–4 characters"
                                />
                                <Text variant="bodySm" tone="subdued">
                                    Prefix should be 1–4 characters long.
                                </Text>
                            </BlockStack>
                        )}
                    </BlockStack>
                </Card>

                {/* ===== EMAIL + NOTE ===== */}
                <div
                    style={{
                        display: "grid",
                        gridTemplateColumns: "60% 40%",
                        gap: "2rem",
                        alignItems: "start",
                    }}
                >
                    <Card>
                        <BlockStack gap="100">
                            <Text variant="headingMd" as="h2">
                                Gift Card Settings
                            </Text>
                            <Checkbox
                                label="Send generated cards to Gmail(s)"
                                checked={sendEmail}
                                onChange={setSendEmail}
                            />
                            {sendEmail && (
                                <BlockStack gap="200">
                                    <TextField
                                        label="Recipient Emails"
                                        value={emailList}
                                        onChange={setEmailList}
                                        onBlur={() =>
                                            setErrors((e) => ({
                                                ...e,
                                                emailList: validateEmails(
                                                    emailList
                                                ),
                                            }))
                                        }
                                        error={errors.emailList}
                                        autoComplete="off"
                                        placeholder="email1@gmail.com, email2@gmail.com"
                                    />
                                    <Text variant="bodySm" tone="subdued">
                                        Separate multiple emails with commas.
                                    </Text>
                                </BlockStack>
                            )}
                        </BlockStack>
                    </Card>

                    <Card>
                        <BlockStack gap="100">
                            <Text variant="headingSm" as="h3">
                                Internal Note
                            </Text>
                            <TextField
                                label=""
                                value={internalNote}
                                onChange={(val) => {
                                    if (val.length <= 120) setInternalNote(val);
                                }}
                                autoComplete="off"
                                multiline={4}
                                showCharacterCount
                                maxLength={120}
                                helpText="Max 120 characters"
                            />
                        </BlockStack>
                    </Card>
                </div>

                {/* ===== ACTION BUTTONS ===== */}
                <div
                    style={{
                        display: "flex",
                        justifyContent: "flex-end",
                        gap: "1rem",
                        marginTop: "2rem",
                    }}
                >
                    <Button variant="secondary" onClick={() => console.log("Cancelled")}>
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSubmit}
                        loading={submitting}
                        disabled={currentTotal >= planLimit}
                    >
                        {currentTotal >= planLimit ? "Limit Reached" : "Create"}
                    </Button>
                </div>
            </BlockStack>
        </Page>
    );
}
