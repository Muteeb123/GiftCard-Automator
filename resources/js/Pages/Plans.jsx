import React from "react";
import {
    Page,
    Text,
    Button,
    BlockStack,
    InlineStack,
} from "@shopify/polaris";
import { usePage } from "@inertiajs/react";

export default function PlansPage() {
    const page = usePage().props;
    const query = page?.ziggy?.query;

    const plans = [
        {
            id: 1,
            name: "Starter",
            description: "For small businesses just getting started",
            price: "$13.99",
            period: "/ per month",
            buttonText: "Get Started",
            features: [
                "Up to 1000 gift cards/month",
                "Reporting and analytics",
                "Email delivery",
            ],
            tag: "Active",
            tagColor: "#22c55e",
            bg: "#fff",
            textColor: "#000",
        },
        {
            id: 2,
            name: "Pro",
            description: "For high-volume merchants & advanced features",
            price: "$120",
            period: "/ per month",
            buttonText: "Get Started",
            features: [
                "Up to 3000 gift cards/month",
                "Reporting and analytics",
                "Email delivery",
                "Gift card balance tracking",
            ],
            tag: "Recommended",
            tagColor: "#60a5fa",
            bg: "linear-gradient(to bottom, #111111, #3C3C3C)",
            textColor: "#fff",
            isHighlighted: true,
        },
        {
            id: 3,
            name: "Growth",
            description: "For growing stores with regular sales",
            price: "$34.99",
            period: "/ per month",
            buttonText: "Get Started",
            features: [
                "Up to 2000 gift cards/month",
                "Reporting and analytics",
                "Email delivery",
            ],
            bg: "#fff",
            textColor: "#000",
        },
    ];

    const handleGetStarted = async (planName) => {
        try {
            const response = await fetch(route("billing.create", query), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
                body: JSON.stringify({ plan_id: planName }),
            });

            const data = await response.json();
            if (response.ok) {
                alert(`🎉 ${data.message}`);
                window.location.reload();
            } else {
                alert(
                    `❌ ${data.errors ? JSON.stringify(data.errors) : data.message
                    }`
                );
            }
        } catch (error) {
            console.error("Error subscribing to plan:", error);
            alert("⚠️ Something went wrong while subscribing. Please try again.");
        }
    };

    return (
        <Page title="Pricing and Plans" subtitle="Choose the plan that fits your business needs."

        >
            <div
                style={{
                    display: "flex",
                    justifyContent: "center",
                    alignItems: "stretch",
                    gap: "2rem",
                    marginTop: "2rem",
                    flexWrap: "wrap",
                }}
            >
                {plans.map((plan) => (
                    <div
                        key={plan.name}
                        style={{
                            background: plan.bg,
                            color: plan.textColor,
                            width: "280px",
                            borderRadius: "16px",
                            boxShadow: plan.isHighlighted
                                ? "0px 6px 20px rgba(0,0,0,0.25)"
                                : "0px 2px 8px rgba(0,0,0,0.08)",
                            padding: "2rem 1.5rem",
                            display: "flex",
                            flexDirection: "column",
                            justifyContent: "space-between",
                            position: "relative",
                            border: plan.isHighlighted
                                ? "none"
                                : "1px solid rgba(0,0,0,0.1)",
                        }}
                    >
                        {/* Tag */}
                        {plan.tag && (
                            <div
                                style={{
                                    position: "absolute",
                                    top: "12px",
                                    right: "12px",
                                    backgroundColor: plan.tagColor,
                                    color: "#fff",
                                    fontSize: "12px",
                                    fontWeight: "600",
                                    padding: "4px 10px",
                                    borderRadius: "12px",
                                }}
                            >
                                {plan.tag}
                            </div>
                        )}

                        {/* Content */}
                        <BlockStack gap="400" align="center">
                            <Text
                                as="h2"
                                variant="headingLg"
                                fontWeight="semibold"
                                alignment="center"
                            >
                                {plan.name}
                            </Text>
                            <Text tone="subdued" alignment="center">
                                {plan.description}
                            </Text>

                            <div style={{ textAlign: "center" }}>
                                <InlineStack gap="200" align="end">
                                    <Text as="h3" variant="heading2xl" fontWeight="bold">
                                        {plan.price}
                                    </Text>
                                    <Text tone="subdued" style={{ alignSelf: "flex-end", marginBottom: "4px" }}>
                                        {plan.period}
                                    </Text>
                                </InlineStack>
                            </div>

                            <div style={{ marginTop: "1.5rem", width: "100%" }}>
                                <Button
                                    fullWidth
                                    size="large"
                                    onClick={() => handleGetStarted(plan.id)}
                                    style={{
                                        backgroundColor: plan.isHighlighted ? "#e5e7eb" : "#000",
                                        color: plan.isHighlighted ? "#000" : "#fff",
                                        borderRadius: "8px",
                                        fontWeight: "600",
                                        padding: "10px 0",
                                    }}
                                >
                                    {plan.buttonText}
                                </Button>
                            </div>

                            <div
                                style={{
                                    marginTop: "1.5rem",
                                    borderTop:
                                        plan.isHighlighted && plan.bg
                                            ? "1px solid rgba(255,255,255,0.1)"
                                            : "1px solid rgba(0,0,0,0.1)",
                                    paddingTop: "1.5rem",
                                    textAlign: "left",
                                    width: "100%",
                                }}
                            >
                                <Text fontWeight="medium">Features</Text>
                                <ul
                                    style={{
                                        listStyle: "none",
                                        padding: 0,
                                        marginTop: "1rem",
                                        display: "flex",
                                        flexDirection: "column",
                                        gap: "0.5rem",
                                    }}
                                >
                                    {plan.features.map((feature, i) => (
                                        <li
                                            key={i}
                                            style={{
                                                display: "flex",
                                                alignItems: "center",
                                                gap: "8px",
                                                color: plan.textColor,
                                            }}
                                        >
                                            <div
                                                style={{
                                                    width: "6px",
                                                    height: "6px",
                                                    backgroundColor: plan.textColor,
                                                    borderRadius: "50%",
                                                }}
                                            />
                                            <span style={{ fontSize: "14px" }}>{feature}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </BlockStack>
                    </div>
                ))}
            </div>
        </Page>
    );
}
